<?php
namespace App\Http\Controllers\V2\Customers;

use App\Classes\K_HelpersV1;
use App\Classes\PagoCashClass;
use App\Classes\StripeCustomClass;
use App\Classes\YappyDriverClass;
use App\Http\Controllers\Controller;
use App\Models\Configuration;
use App\Models\Customer;
use App\Models\Driver;
use App\Models\DriverService;
use App\Models\FcmToken;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserPaymentController extends Controller
{
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
        $customer = Customer::find($user->userable_id);
        $stripeClass = new StripeCustomClass();
        if(empty($user->stripe_id)){                
            return response()->json( array(
                "error" => true, 
                "msg" => "CustomerId esta vacio"
            ) , self::HTTP_NOT_FOUND );
        }
        $result = $stripeClass->getPaymentMethods($user->stripe_id);
        return response()->json( array(
            "error"=>false,
            "data" => $result["data"], 
            "has_more" => $result["has_more"],
            //"default_pm" => $this->getDefaultPaymentMethodCustomerInfo($customerId)
        ) , self::HTTP_OK );
    }
    public function create() //stripeCustomer
    {
        //
        $user = request()->user();
        $customer = Customer::find($user->userable_id);
        //$stripeClass = new StripeCustomClass();
        try {
            if(!empty($user->stripe_id)){
                return response()->json([
                    "error" => false,
                    "msj" => "Stripe Customer ya existe"
                ], self::HTTP_CREATED);
            }
            $stripeCustomer = $user->createAsStripeCustomer();
            //$user->stripe_id = $stripeClass->createCustomer($user->id, $customer->nombres, $customer->apellidos, $user->email, null);
            $user->stripe_id = $stripeCustomer->id;
            if($user->save()){

                $userInfo = [
                    "email" => $user->email,
                    "nombre" => $customer->nombres,
                    "apellidos" => $customer->apellidos,
                    "celular" => $customer->telefono,
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
    public function show($id) //setupIntent, 
    {
        //
        
        switch ($id) {
            case 'setup_intent':
                $user = request()->user();
                $request = request();
                $customer = Customer::find($user->userable_id);
                $stripeClass = new StripeCustomClass();
                $stripeCustomerId = $user->stripe_id;
                
                $response = $stripeClass->createSetupIntent($stripeCustomerId);
                if(!empty(request()->ek) && request()->ek == 'true'){
                    $ephemeralKey = $stripeClass->createEphemeralKeys($stripeCustomerId);
                    $response = [
                        "setupIntent" => $response["client_secret"],
                        'ephemeralKey' => $ephemeralKey->secret,
                        'customer' => $stripeCustomerId,
                        'publishableKey' => $stripeClass->getPublicKey(),
                    ];
                }
                return response()->json([
                    "error" => false,
                    "msj" => "Setup intent creado exitosamente",
                    "data" => $response,
                ], self::HTTP_OK);
                break;
            case 'deposit_stripe':
                $user = request()->user();
                $request = request();
                $stripeClass = new StripeCustomClass();
                $pmId =  $request->payment_method_id;
                $value =  $request->amount;
                $taxes = Configuration::find(Configuration::IMPUESTO_PAIS)->comision; //Obtener el Impuesto del país
                $porcentTaxes = ($taxes / 100.0);
                $vTaxes = ($value * $porcentTaxes);
                $result = $stripeClass->confirmPayment($user->stripe_id, $pmId, $value * 100.0);
                if($result["error"]){
                    return response()->json($result, self::HTTP_BAD_REQUEST);
                }   
                //register deposit amount in db
                Transaction::create([
                    "user_id" => $user->id,
                    "service_id" => null,
                    "type" => "0",
                    "amount" => $value,
                    "currency" => "USD",
                    "transaction_id" => $result["payment_intent"]["id"],
                    "status" => $result["payment_intent"]["status"],
                    "gateway" => "Stripe",
                    "receipt_url" => "",
                ]);
                K_HelpersV1::getInstance()->setDepositStripe($user->id, $value, $result["payment_intent"]);
                $userBalance = calculateDriverBalance($user->id, DB::table("transactions"));
                if (round($userBalance, 2) >= Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision ) {
                    $user->status = "Activo";
                    $user->save();
                    K_HelpersV1::getInstance()->enableDriver($user->id);
                }
                $response = array('error' => false, 'msg' => 'Deposito registrado');
                return response()->json($response);
                break;
            default:
                # code...
                break;
        }
    }
    public function update(Request $request, $id) //paymentIntent
    {
        $user = request()->user();
        $customer = Customer::find($user->userable_id);
        if($request->isMethod('put')){
            $serviceId = $request->service_id;
            $service = Service::find($serviceId);
            $payment_method = $request->payment_method;
            $driverId = $request->driver_id;

            $driverPrice = DriverService::where([
                ["driver_id", "=", $driverId],
                ["service_id", "=", $serviceId],
            ])->first()->suggested_price;
            $stripeClass = new StripeCustomClass();
            try{
                $taxes = Configuration::find(Configuration::IMPUESTO_PAIS)->comision; //Obtener el Impuesto del país
                $porcentTaxes = ($taxes / 100.0);
                $vTaxes = ($driverPrice * $porcentTaxes);
                $result = $stripeClass->confirmPayment($user->stripe_id, $payment_method, $driverPrice * 100.0);
                if($result["error"]){
                    return response()->json($result, self::HTTP_BAD_REQUEST);
                }
                Transaction::create([
                    "user_id" => $user->id,
                    "service_id" => $serviceId,
                    "type" => "0",
                    "amount" => $driverPrice,
                    "currency" => "USD",
                    "transaction_id" => $result["payment_intent"]["id"],
                    "status" => $result["payment_intent"]["status"],
                    "gateway" => "Stripe",
                    "receipt_url" => "",
                ]);
                //$this->notifyServiceStatus($serviceId, true, "Estado del servicio", "Oferta aceptada");                
                $userDriver = User::where([
                    ["userable_id", "=", $driverId],
                    ["userable_type", "=", Driver::class],
                ])->first();
                $tokens = FcmToken::where("user_id", $userDriver->id)->cursor();
                foreach ($tokens as $key => $token) {
                    notifyToDriver($token, "Estado del servicio", "Oferta aceptada", [
                        //"url" => "ServActScreen",
                        "key" => $service->estado == "Reserva" ? "SCHEDULE_ACCEPTED_PRICE" : "ACCEPTED_PRICE",
                    ]);
                }
                return response()->json($result, self::HTTP_OK);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                $result = [
                    "error" => true,
                    "amount" => $driverPrice * 100,
                    "msg" => $e->getError()->message
                ];
                return response()->json($result, self::HTTP_BAD_REQUEST);

                //throw $th;
            }
           
        }
        $response = array('error' => true, 'msg' => 'Servicio no encontrado' );
        return response()->json( $response , self::HTTP_NOT_FOUND );	
    }
    public function destroy($id){ //remove, 
        $user = request()->user();
        //StripeCustomClass::getInstance()->deletePaymentMethods($user->stripe_id, $id);
        StripeCustomClass::getInstance()->removePaymentMethod($id);
        return response(null, 204);
    }
}