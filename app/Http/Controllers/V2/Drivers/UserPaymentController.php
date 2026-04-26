<?php

namespace App\Http\Controllers\V2\Drivers;

use App\Classes\K_HelpersV1;
use App\Classes\PagoCashClass;
use App\Classes\StripeCustomClass;
use App\Classes\YappyDriverClass;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\Driver;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPaymentController extends Controller
{
    private $externTransactionPage = "https://app.kamgus.com/#/transaction-response";
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //payment_methods
    {
        //
        //StripeCustomClass
        $user = request()->user();
        $driver = Driver::find($user->userable_id);
        $stripeClass = new StripeCustomClass();
        if(empty($user->stripe_id)){                
            return response()->json( array(
                "error" => true, 
                "msg" => "CustomerId esta vacio"
                ) , self::HTTP_NOT_FOUND );
        }
        try {
            $result = $stripeClass->getPaymentMethods($user->stripe_id);
            //code...
            return response()->json( array(
                "error"=>false,
                "data" => $result["data"], 
                "has_more" => $result["has_more"],
                //"default_pm" => $this->getDefaultPaymentMethodCustomerInfo($customerId)
            ) , self::HTTP_OK );
        } catch (\Exception $e) {
            //$user->stripe_id = null;
            //$user->save();
            return response()->json([
                "error" => true,
                "msg" => $e->getMessage(),
            ], 400);
        }
    }
    public function create() //stripeCustomer
    {
        //
        $user = request()->user();
        $driver = Driver::find($user->userable_id);
        //$stripeClass = new StripeCustomClass();
        try {
            if(!empty($user->stripe_id)){
                return response()->json([
                    "error" => false,
                    "msj" => "Stripe Customer ya existe"
                ], self::HTTP_ACCEPTED);
            }
            $stripeCustomer = $user->createAsStripeCustomer();
            //$user->stripe_id = $stripeClass->createCustomer($user->id, $driver->nombres, $driver->apellidos, $user->email, null);
            $user->stripe_id = $stripeCustomer->id;
            if($user->save()){

                $userInfo = [
                    "email" => $user->email,
                    "nombre" => $driver->nombres,
                    "apellidos" => $driver->apellidos,
                    "celular" => $driver->telefono,
                    //"idusuarios" => $user->id,
                ];
                K_HelpersV1::getInstance()->createCustomer($user->id, $userInfo, $user->stripe_id);
                return response()->json([
                    "error" => false,
                    "msj" => "Stripe Customer creado exitosamente"
                ], self::HTTP_OK);
            };
        } catch (\Throwable $th) {
            //throw $th;
            return response()->json([
                "error" => true,
                "msj" => $th->getMessage(),
            ], self::HTTP_BAD_GATEWAY);
        }

    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) //setupIntent, 
    {
        //
        switch ($id) {
            case 'setup_intent':
                $user = request()->user();
                $driver = Driver::find($user->userable_id);
                $stripeClass = new StripeCustomClass();

                $response = $stripeClass->createSetupIntent($user->stripe_id);
                if(!empty(request()->ek) && request()->ek == 'true'){
                    $ephemeralKey = $stripeClass->createEphemeralKeys($user->stripe_id);
                    $response = [
                        "setupIntent" => $response["client_secret"],
                        'ephemeralKey' => $ephemeralKey->secret,
                        'customer' => $user->stripe_id,
                        'publishableKey' => $stripeClass->getPublicKey(),
                    ];
                }
                return response()->json([
                    "error" => false,
                    "msj" => "Setup intent creado exitosamente",
                    "data" => $response,
                ], self::HTTP_OK);
                break;
            
            default:
                # code...
                break;
        }
    }
    public function show2($id) {
        switch ($id) {
            case 'yappy_success':
                $domain = YappyDriverClass::DOMAIN;
                $request = request();
                if (isset($request->orderid) && isset($request->status) && isset($domain) && isset($request->hash)) {
                    $yappy = YappyDriverClass::getInstance();
                    $_GET["domain"] = empty($_GET["domain"]) ? $domain : $_GET["domain"];
                    $success = $yappy->validateHash();
                    //dd($request->hash);
                    if ($success) {
                        $transaction = Transaction::where("id", $request->orderid)->first();                        
                        $transaction->transaction_id = $request->hash;
                        $transaction->status = "succeeded";
                        $transaction->updated_at = date("Y-m-d H:i:s");
                        $transaction->save();
                        $userId = $transaction->user_id;
                        $user = User::find($userId);
                        $balance = calculateDriverBalance($userId, DB::table("transactions"));
                        if (($user->status == 3 || $user->status == "Bloqueado") && (round($balance, 2) >= Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision) ) {
                            $user->status = "Activo";
                            $user->save();
                            K_HelpersV1::getInstance()->enableDriver($userId);
                        }
                        
                    }
                }
                return redirect($this->externTransactionPage."/driver/yappy/successfully/".$request->orderid);
                //return view("drivers.transaction_success", [
                //    "status" => "exitosamente",
                //    "orderId" => $request->orderId,
                //]);
               
                break;
            case 'yappy_fail':
                $domain = YappyDriverClass::DOMAIN;
                $request = request();
                if (isset($request->orderid) && isset($request->status) && isset($domain)) {
                    $yappy = YappyDriverClass::getInstance();
                    $transaction = Transaction::where([
                        ["service_id", "=", $request->orderid],
                        ["created_at", ">=", date("Y-m-d H:i:s")],
                    ])->first();                        
                    $transaction->status = "failed";
                    $transaction->updated_at = date("Y-m-d H:i:s");
                    $transaction->save();
                    $userId = $transaction->user_id;
                    $user = User::find($userId);
                
                    
                }
                return redirect($this->externTransactionPage."/driver/yappy/fail/".$request->orderid);

                //return view("drivers.transaction_error", [
                //    "status" => "completamente",
                //    "orderId" => $request->orderId,
                //]);
                break;
            
            default:
                # code...
                break;
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id) //setDepositStripe, setDepositPagoCash, setDepositYappy
    {
        $user = request()->user();
        $driver = Driver::find($user->userable_id);
        $stripeClass = new StripeCustomClass();
        switch ($id) {
            case 'deposit_stripe':
                $pmId =  $request->payment_method_id;
                $value =  $request->amount;
                //$taxes = DB::select('SELECT configurations.comision from configurations where id=5')[0]->comision; //Obtener el Impuesto del país
                $taxes = Configuration::find(Configuration::IMPUESTO_PAIS)->comision; //Obtener el Impuesto del país
                $porcentTaxes = ($taxes / 100.0);
                $vTaxes = ($value * $porcentTaxes);
                $value = $value - $vTaxes;
                $result = $stripeClass->confirmPayment($user->stripe_id, $pmId, ($value + $vTaxes) * 100.0);
                if($result["error"]){
                    return response()->json($result, self::HTTP_BAD_REQUEST);
                }   
                //register deposit amount in db
                Transaction::create([
                    "user_id" => $user->id,
                    "service_id" => null,
                    "type" => "0",
                    "amount" => $value,
                    "tax" => $vTaxes,
                    "total" => $value + $vTaxes,
                    "currency" => "USD",
                    "transaction_id" => $result["payment_intent"]["id"],
                    "status" => $result["payment_intent"]["status"],
                    "gateway" => "Stripe",
                    "receipt_url" => "",
                ]);
                K_HelpersV1::getInstance()->setDepositStripe($user->id, $value + $vTaxes, $result["payment_intent"]);
                $userBalance = calculateDriverBalance($user->id, DB::table("transactions"));
                if (round($userBalance, 2) >= Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision ) {
                    $user->status = "Activo";
                    $user->save();
                    K_HelpersV1::getInstance()->enableDriver($user->id);
                }
                $response = array('error' => false, 'msg' => 'Deposito registrado');
                return response()->json($response);
                break;
            case 'deposit_pago_cash':
                $userId = $user->id;
                $value = $request->amount;
                //$taxes = DB::select('SELECT configurations.comision from configurations where id=5')[0]->comision; //Obtener el Impuesto del país
                $taxes = Configuration::find(Configuration::IMPUESTO_PAIS)->comision; //Obtener el Impuesto del país
                $porcentTaxes = ($taxes / 100.0);
                $vTaxes = ($value * $porcentTaxes);
                $value = $value - $vTaxes;
                $data = array(
                    'user_id' =>$userId,
                    "service_id" => null,
                    "type" => "0",
                    "currency" => "USD",
                    'amount' => $value,
                    'tax' => $vTaxes,
                    'total' => $value + $vTaxes,
                    'gateway' => "pago_cash",
                    'created_at' => date("Y-m-d H:i:s"),
                    "transaction_id" => '',
                    "status" => 'pending',
                    "receipt_url" => "",
                );
                $charge = Transaction::create($data);
                $chargeId = $charge->id;
                $ticket = PagoCashClass::getInstance()
                    ->init($value + $vTaxes, $user->email, $driver->telefono, "Deposito de conductor ".$driver->nombres." ".$driver->apellidos)
                    ->generateTicket($chargeId, $user->id);

                if( is_string($ticket) ){
                    
                    DB::table("transactions")->where("id", $chargeId)->update([
                        'transaction_id' => $ticket,
                        'receipt_url' => 'PCPEND_0',
                        'created_at' => date("Y-m-d H:i:s"),
                    ]);
                    K_HelpersV1::getInstance()->setDepositPagoCash($user->id, $value, $vTaxes, $ticket);
                    $response = array('error' => false, 'msg' => 'Transacción iniciada. Por favor revise su bandeja de correo para continuar con el proceso de pago');
                    return response()->json( $response , self::HTTP_OK );
                }else{
                    $response = array('error' => true, 'msg' => 'error 2', "amount" => $data["amount"] );
                    return response()->json( $response , self::HTTP_OK );	
                }
                break;
            case 'deposit_yappy':
                $userId = $user->id;
		        $value = $request->amount;
                //$taxes = DB::select('SELECT configurations.comision from configurations where id=5')[0]->comision; //Obtener el Impuesto del país
                $taxes = Configuration::find(Configuration::IMPUESTO_PAIS)->comision; //Obtener el Impuesto del país
                $porcentTaxes = ($taxes / 100.0);
                $vTaxes = ($value * $porcentTaxes);
                $value = $value - $vTaxes;
                $total = $value  + $vTaxes;
                $data = array(
                    'user_id' =>$userId,
                    "service_id" => null,
                    "type" => "0",
                    "currency" => "USD",
                    'amount' => $value,
                    'tax' => $vTaxes,
                    'total' => $total,
                    'gateway' => "yappy",
                    'created_at' => date("Y-m-d H:i:s"),
                    "transaction_id" => '',
                    "status" => 'pending',
                    "receipt_url" => "",
                );
                $charge = Transaction::create($data);
                $chargeId = $charge->id;
               
                $apiResponse = YappyDriverClass::getInstance()
                    ->setPaymenParameters($total, $value, $vTaxes, $chargeId, $driver->telefono)
                    ->startPayment();
                    
                if(empty($apiResponse["success"]) || !($apiResponse["success"])){
                    $response = $apiResponse;
                    $response["error"] = true;
                    Transaction::where("id", $chargeId)->delete();
                    return response()->json( $response , self::HTTP_BAD_GATEWAY );	
                }
                $charge->receipt_url = $apiResponse["url"];
                $charge->save();
                //if( false ){
                K_HelpersV1::getInstance()->setDepositYappy($user->id, $value, $taxes, $apiResponse);
                $response = array('error' => false, 'msg' => '', 'data'=> $apiResponse);
                return response()->json( $response , self::HTTP_OK );
                break;
            case 'take_out':
                # code...
                /*$userId = $request->id_usuario;
                $value = $request->amount;
                $totalBalance = calculateDriverBalance($user->id, DB::table("transactions"));
                if($totalBalance > 0){                  
                    $params = [
                        'user_id' =>$userId,
                        "service_id" => null,
                        "type" => "0",
                        "currency" => "USD",
                        'amount' => -1 * $value,
                        'tax' => 0,
                        'gateway' => "",
                        'created_at' => date("Y-m-d H:i:s"),
                        "transaction_id" => '',
                        "status" => 'pending',
                        "receipt_url" => "preordered",
                    ];
                    $charge = Transaction::create($params);
                    $chargeId = $charge->id;
                    $users = User::role('Administrador')->cursor();
                    foreach ($users as $key => $admin) {
                        Mail::to($admin->email)->send();
                    }
                    $response = array('error' => true, 'msg' => 'Balance no disponible' );
                    return response()->json($response, self::HTTP_NOT_FOUND);
                }
                $response = array('error' => true, 'msg' => 'Fondos insuficientes. Balance: '.($totalBalance < 0 ? "-$".abs($totalBalance): "$".$totalBalance)." " );
                return response()->json($response, self::HTTP_NOT_FOUND);
                */
                break;
            case 'addPM':
                return;
                return $stripeClass->addPaymentMethod($user->stripe_id, [
                    'number' => '4111111111111111',
                    'exp_month' => 8,
                    'exp_year' => 2024,
                    'cvc' => '314',
                ]);
                return response()->json($stripeClass->getPaymentMethods($user->stripe_id));
                break;
            
            default:
                # code...
                break;
        }
    }
    public function depositResponse($id, $response){
        $request = request();
        switch ($id) {
            case 'yappy':
                switch ($response) {
                    case 'success':
                        return view("drivers.transaction_success", [
                            "status" => "exitosamente",
                            "orderId" => $request->orderId,
                        ]);
                        break;
                    
                    default:
                        return view("drivers.transaction_error", [
                            "orderId" => $request->orderId,
                        ]);
                        break;
                }
                break;
            case 'pagoCash':
                switch ($response) {
                    case 'success':
                        return view("drivers.transaction_success", [
                            "status" => "exitosamente",
                            "orderId" => $request->CodOper,
                        ]);
                        break;
                    
                    default:
                        return view("drivers.transaction_error", [
                            "orderId" => $request->CodOper,
                        ]);
                        break;
                }
                break;
            
            default:
                # code...
                break;
        }
    }
//
    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
