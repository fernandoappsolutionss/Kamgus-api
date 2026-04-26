<?php

namespace App\Http\Controllers\V2\WebApp;

use App\Classes\K_HelpersV1;
use App\Classes\StripeCustomClass;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserPaymentController extends Controller
{
    /**
     * Display a listing of the payment method.
     *
     * @return \Illuminate\Http\Response
     */
    public function index() //payment_methods
    {
        //
        //StripeCustomClass
        $user = request()->user();
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
        $userable = $user->userable;
        //$stripeClass = new StripeCustomClass();
        try {
            if(!empty($user->stripe_id)){
                return response()->json([
                    "error" => false,
                    "msj" => "Stripe Customer ya existe"
                ], self::HTTP_ACCEPTED);
            }
            $stripeCustomer = $user->createAsStripeCustomer();
            //$user->stripe_id = $stripeClass->createCustomer($user->id, $userable->nombres, $userable->apellidos, $user->email, null);
            $user->stripe_id = $stripeCustomer->id;
            if($user->save()){

                $userInfo = [
                    "email" => $user->email,
                    "nombre" => $userable->nombres,
                    "apellidos" => $userable->apellidos,
                    "celular" => $userable->telefono,
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
     * Store a newly created payment method in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified payment method.
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
            
            default:
                # code...
                break;
        }
    }

    /**
     * Update the specified payment method in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified payment method from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id){ //remove, 
        $user = request()->user();
        //StripeCustomClass::getInstance()->deletePaymentMethods($user->stripe_id, $id);
        StripeCustomClass::getInstance()->removePaymentMethod($id);
        return response(null, 204);
    }
}
