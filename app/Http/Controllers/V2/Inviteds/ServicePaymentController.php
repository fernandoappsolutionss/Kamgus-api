<?php

namespace App\Http\Controllers\V2\Inviteds;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Classes\YappyInvitedClass;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\DriverService;
use App\Models\Service;
use Illuminate\Http\Request;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

use Carbon\Carbon;
use DB;
class ServicePaymentController extends Controller
{
    private $externTransactionPage = "https://app.kamgus.com/#/transaction-response";
    /**
     * Display a listing of the resource.ServicePaymentController
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //payment_methods_invitado/{id}?customer_id
    {
        //
    }
    public function createCustomer(){ //createCheckoutSessionInvitado

    }
    public function createCheckout(){ //createCheckoutSessionInvitado

    }
    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request) //makeYappyTransactionInvitado, payment_methods_invitado/{id}?customer_id    
    {
        //
        $type = $request->type;
        switch ($type) {
            case '1':
                # code...
                return $this->makeStripeCheckoutTransaction($request);
                break;
            case '2':
                return $this->makeYappyTransaction($request);
                break;
            
            default:
                return response("Invalid option");
                break;
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id) //paymentSuccessfully
    {
        //
        switch ($id) {
            case 'yappy_success':
                $domain = YappyInvitedClass::DOMAIN;
                $validator = Validator::make(request()->all(), [
                    'orderid'    => 'required',
                    'status'    => 'required',
                    'hash'    => 'required',
                ]);
                if($validator->fails()){
                    return response()->json(['error' => $validator->errors()], self::HTTP_BAD_REQUEST);
                }
               
                $request = request();
                $_GET["domain"] = empty($_GET["domain"]) ? $domain : $_GET["domain"];
                if (isset($request->orderid) && isset($request->status) && isset($domain) && isset($request->hash)) {
                    //header('Content-Type: application/json');
                    $yappy = YappyInvitedClass::getInstance();
                    $success = $yappy->validateHash();
                    if ($success) {
                        // Si es true, se debe cambiar el estado de la orden en la base de datos
                        $service = Service::find($request->orderid);

                        $transaction = Transaction::where("service_id", $request->orderid)->first();
                        $transaction->transaction_id = $request->hash;
                        $transaction->status = "succeeded";
                        $transaction->save();
                        K_HelpersV1::getInstance()->registerYappyTransaction(["transaction_id" => $request->hash, "status" => "succeeded", "service_id" => $request->orderid,], $service->user_id);
                        
                        $valor = 0;
                        if(!empty($service)){
                            $valor = $service->precio_sugerido;
                        }
                        //return "Transacción finalizada";
                        
                        return redirect($this->externTransactionPage."/customer/yappy/successfully/".$request->orderid);
                        header('Content-Type: text/html');
                        return view("inviteds.yappy_service_transaction", [
                            "response" => "success",
                            "serviceId" => $request->orderid,
                            "valor" => $valor,
                        ]);
                    }
                    return(['success' => $success]);
                } 
                $response = array('error' => false, 'msg' => 'Transacción finalizada exitosamente');
                return response()->json( $response , self::HTTP_OK );
                break;
            case 'yappy_fail':
                $domain = YappyInvitedClass::DOMAIN;
                $request = request();
                $_GET["domain"] = empty($_GET["domain"]) ? $domain : $_GET["domain"];
                if (isset($request->orderid) && isset($request->status) && isset($domain) && isset($request->hash)) {
                    //header('Content-Type: application/json');
                    $yappy = YappyInvitedClass::getInstance();
                    $success = $yappy->validateHash();
                    if ($success) {
                        // Si es true, se debe cambiar el estado de la orden en la base de datos
                        $service = Service::find($request->orderid);

                        $transaction = Transaction::where("service_id", $request->orderid)->first();
                        $transaction->status = "failed";
                        $transaction->save();
                 
                        K_HelpersV1::getInstance()->registerYappyTransaction(["transaction_id" => null, "status" => "failed", "service_id" => $request->orderid], $service->user_id);
                        $valor = 0;
                        if(!empty($service)){
                            $valor = $service->precio_sugerido;
                        }
                        return redirect($this->externTransactionPage."/customer/yappy/fail/".$request->orderid);
                        header('Content-Type: text/html');
                        return view("inviteds.yappy_service_transaction", [
                            "response" => "fail",
                            "serviceId" => $request->orderid,
                            "valor" => $valor,
                        ]);
                    }
                    return(['success' => $success]);
                } 
                $response = array('error' => false, 'msg' => 'Error durante la transacción');
                return response()->json( $response , self::HTTP_OK );
                break;
            case 'checkout_status':
                $serviceId = request()->idservicio;
                $service = Service::find($serviceId);
                $transaction = Transaction::where("service_id", $serviceId)->first();
                if(strpos($transaction->transaction_id, "cs_") !== false && ($transaction->status == "open" || $transaction->status == "succeeded" || $transaction->status == "complete")){                
                    try {
                        $cs = StripeCustomClass::getInstance()->getCheckoutSession($service->transaction_id);
                        $tData = [
                            "status" => $cs["status"],
                            "transaction_id" => $cs["id"],
                        ];
                        Transaction::where("service_id", $serviceId)->update($tData);
                        $tData["service_id"] = $serviceId;
                        K_HelpersV1::getInstance()->updateStripeTransaction($tData, $service->user_id);
                        $response = array('error' => false, 'msg' => 'Checkout iniciado', "data" => $cs );
                        return response()->json( $response , self::HTTP_OK );	
                        return;
                    } catch (\Exception $e) {
                        $response = array('error' => true, 'msg' => $e->getMessage() );
                        return response()->json( $response , self::HTTP_NOT_FOUND );	
                    }
                }
                $response = array('error' => true, 'msg' => 'Checkout no disponible' );
                break;
            case 'paymentSuccessfully':
                $response = array('error' => false, 'msg' => 'Pago exitoso.' );
		        return response()->json( $response , self::HTTP_OK );	
                break;
            default:
                $transaction = Transaction::where([
                    ["service_id", "=", $id],
                ])->first();
                return response()->json([
                    "data" => [
                        "Response" => $transaction->status,
                    ]
                ]);
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
    public function update(Request $request, $id) //refreshCheckoutSessionStatusInvitado?idservicio
    {
        //
        // hardcoded Stripe key removed; use env('STRIPE_SECRET') / env('STRIPE_SECRET_TEST')

        $serviceId = $id;
        $service = Service::find($serviceId);
        $transaction = Transaction::where("service_id", $serviceId)->first();
        if(strpos($transaction->transaction_id, "cs_") !== false && ($transaction->status == "open" || $transaction->status == "succeeded" || $transaction->status == "complete")){
            try {
                $cs = StripeCustomClass::getInstance()->getCheckoutSession($transaction->transaction_id);
                //dd( $cs["status"]);
                $tData = [
                    "status" => $cs["status"],
                    "transaction_id" => $cs["id"],
                ];
                Transaction::where("service_id", $serviceId)->update($tData);
                $tData["service_id"] = $serviceId;
                K_HelpersV1::getInstance()->updateStripeTransaction($tData, $service->user_id);
                $response = array('error' => false, 'msg' => 'Checkout Finalizado', "data" => $cs );
                return response()->json( $response , self::HTTP_OK );	
                dd($transaction->transaction_id);
                return;
            } catch (\Exception $e) {
                $response = array('error' => true, 'msg' => $e->getMessage() );
                return response()->json( $response , self::HTTP_NOT_FOUND );	
            }
        }
        $response = array('error' => true, 'msg' => 'Checkout no disponible' );
        return response()->json( $response , self::HTTP_NOT_FOUND );	
    }

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

    private function makeStripeCheckoutTransaction($request){
        $serviceId = $request->idservicio;
        $driverId = $request->driver_id;
        $service = Service::find($serviceId);
        $driverService = DriverService::where([
            ["service_id", "=", $serviceId],
        ])
        ->whereRaw("SHA2(driver_services.driver_id, 256) = ?", [$driverId])
        ->first();
        // hardcoded Stripe key removed; use env('STRIPE_SECRET') / env('STRIPE_SECRET_TEST')
        $precioSugerido =  !empty($driverService) ? $driverService->suggested_price: 0;

        if(!empty($request->role) && $request->role === "Administrador"){// && !User::find($request->role)->hasRole("Conductor")){
            $service->precio_real = $service->precio_sugerido;
            $precioSugerido = $service->precio_sugerido;
            $driverId = Service::SERVICE_ADMIN_OFFER;
        }
        
        if($service->tipo_pago != 'Card'){
            //return response with message "Service is not available"
            return response()->json([
                "error" => true,
                "msg" => "El pago con tarjeta no esta seleccionado para te servicio",
            ], self::HTTP_BAD_REQUEST);
        }
        //$taxes = DB::select('SELECT configurations.comision from configurations where id=5')[0]->comision; //Obtener el Impuesto del país
        $taxes = Configuration::find(Configuration::IMPUESTO_PAIS)->comision; //Obtener el Impuesto del país
        $porcentTaxes = ($taxes / 100.0);
		$vTaxes = ($precioSugerido * $porcentTaxes);
        $amount = $precioSugerido - $vTaxes;
		$total = $amount + $vTaxes;
        if(strpos($service->transaction_id, "cs_") !== false && ($service->status == "open" || $service->status == "succeeded" || $service->status == "complete")){
            $cs = StripeCustomClass::getInstance()->getCheckoutSession($service->transaction_id);
            $response = array('error' => false, 'msg' => 'Checkout iniciado', "data" => $cs );
            return response()->json( $response , self::HTTP_OK );	
        }
        //dd([$total, $amount, $vTaxes]);
        $transactionData = StripeCustomClass::getInstance()->createCheckoutSessionInvitado($service->id, $driverId, $service->tipo_transporte, $total);
        $data = array(
            'user_id' =>$service->user_id,
            "service_id" => $service->id,
            "type" => "0",
            "currency" => "USD",
            'amount' => $amount,
            'tax' => $vTaxes,
            //'total' => $total,
            'gateway' => "stripe",
            'created_at' => date("Y-m-d H:i:s"),
            "transaction_id" => $transactionData["id"],
            "status" => $transactionData["status"],
            "receipt_url" => "",
        );
        $transaction = Transaction::firstOrNew([
            'user_id' =>$service->user_id,
            "service_id" => $service->id,
        ]);
        $transaction->type = "0";
        $transaction->currency = "USD";
        $transaction->amount = $amount;
        $transaction->tax = $vTaxes;
        $transaction->total = $total;
        $transaction->gateway = "stripe";
        $transaction->created_at = date("Y-m-d H:i:s");
        $transaction->transaction_id = $transactionData["id"];
        $transaction->status = $transactionData["status"];
        $transaction->receipt_url = $transactionData["url"];
        $transaction->save();
        //Transaction::create($data);
        K_HelpersV1::getInstance()->registerStripeTransaction($data, $service->user_id);
        $response = array('error' => false, 'msg' => 'Checkout iniciado', "data" => $transactionData );
        return response()->json( $response , self::HTTP_OK );	
    }
    private function makeYappyTransaction($request){
        $serviceId = $request->id_servicio;
		$driverId= $request->driver_id;
        
        $serviceInfo = DriverService::where([
            ["service_id", "=", $serviceId],
        ])
        ->whereRaw("SHA2(driver_services.driver_id, 256) = ?", [$driverId])
        ->first();
        $service = Service::find($serviceId);
		$userId = $service->user_id;
		$user = User::find($service->user_id);
        $telefono = null;
        if(!empty($user)){
            $telefono = $user->userable->telefono;
        }
        if($service->tipo_pago != 'Card'){
            //return response with message "Service is not available"
            return response()->json([
                "error" => true,
                "msg" => "El pago con tarjeta no esta seleccionado para te servicio",
            ], self::HTTP_BAD_REQUEST);
        }
        $value = $service->precio_real;
        if(!empty($request->role) && $request->role === "Administrador"){// && !User::find($request->role)->hasRole("Conductor")){
            $service->precio_real = $service->precio_sugerido;
            $value = $service->precio_sugerido;
        }else{
            $value = $serviceInfo->suggested_price;
        }
        //$taxes = DB::select('SELECT configurations.comision from configurations where id=5')[0]->comision; //Obtener el Impuesto del país
        $taxes = Configuration::find(Configuration::IMPUESTO_PAIS)->comision; //Obtener el Impuesto del país
        $porcentTaxes = ($taxes / 100.0);
		$vTaxes = ($value * $porcentTaxes);
        $value = $value - $vTaxes;
		$total = $value + $vTaxes;
        $apiResponse = YappyInvitedClass::getInstance()->setPaymenParameters($total, $value, $vTaxes, $serviceId, $telefono)
			->startPayment();
        //dd($apiResponse);
        if(empty($apiResponse["success"]) || !($apiResponse["success"])){
			$response = $apiResponse;
			$response["error"] = true;			
			return response()->json( $response , self::HTTP_BAD_GATEWAY );	
		}
        $transaction = Transaction::firstOrNew([
            'user_id' =>$service->user_id,
            "service_id" => $service->id,
        ]);
        $transaction->type = "0";
        $transaction->currency = "USD";
        $transaction->amount = $value;
        $transaction->tax = $vTaxes;
        $transaction->total = $total;
        $transaction->gateway = "yappy";
        $transaction->created_at = date("Y-m-d H:i:s");
        $transaction->transaction_id = $apiResponse["signature"];
        $transaction->status = 'pending';
        $transaction->receipt_url = $apiResponse["url"];
        $transaction->save();
		
		//if( false ){
		
		$response = array('error' => false, 'msg' => 'cargando servicios', 'data'=> $apiResponse);
		return response()->json( $response , self::HTTP_OK );
    }
}
