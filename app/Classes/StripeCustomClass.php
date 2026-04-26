<?php
namespace App\Classes;


//define("F_ENVIROMENT", 'enviroment');
define("F_ENVIROMENT", config('app.env'));
define("STRIPE_SK", 
    F_ENVIROMENT === 'production' ?
    '***REMOVED-STRIPE-LIVE***' : //Production stripe key
    '***REMOVED-STRIPE-TEST***'
); 
//importando libreria restful

//use Restserver\Libraries\REST_Controller;

class StripeCustomClass {
    private static $instance = null;

    public function __construct() {
        
    } 
    public static function getInstance(){
        if(empty(self::$instance)){
            self::$instance = new StripeCustomClass();
        }
        return self::$instance;
    }
    public function removePaymentMethod($id){
        $stripe = new \Stripe\StripeClient((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);
        return $stripe->paymentMethods->detach(
            $id,
            []
          );
    }
    public function getPublicKey(){
        return (!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST;
    }
    public function getPaymentIntent($paymentIntentId){
        $stripe = new \Stripe\StripeClient((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);
        return $stripe->paymentIntents->retrieve($paymentIntentId, []);

    }

    public function refundPaymentIntent($paymentIntentId, $value, $title = "", $description = ""){
        $stripe = new \Stripe\StripeClient((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);
   
        return $stripe->refunds->create([
            'payment_intent' => $paymentIntentId,
            'amount' => $value,
            'metadata' => [
                'subject' => $title,
                'description' => $description,
            ],
            'reason' => 'requested_by_customer',
            
        ]);
    }
    public function createCustomer($userId, $name, $lastname, $email, $stripeCustomerId = null){
        \Stripe\Stripe::setApiKey((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);

        if(!empty($stripeCustomerId)){ //Evalua si el usuario ya tiene un customer asociado
          
            $customer = \Stripe\Customer::update(
                $stripeCustomerId, 
                [
                    "name" => implode(", ", [$name, $lastname]),
                    "email" => $email,
                ]
            ); //crea un nuevo customer
    
           //$query = $this->db->query('UPDATE customers SET StripeCustomerId = "'.$customer->id.'" WHERE idusuarios ='.$user->idusuarios  );//se le asigna el customer id al usuario
           return $stripeCustomerId;
        }
      
        $customer = \Stripe\Customer::create([
            "name" => implode(", ", [$name, $lastname]),
            "email" => $email,
        ]); //crea un nuevo customer

  
        return $customer->id;
    }
    //Permite crear un Setup Intent para un customer especificado
    public function createSetupIntent($customer_id){
        $stripe = new \Stripe\StripeClient((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);

        $intent = $stripe->setupIntents->create(
        [
            'customer' => $customer_id,
            //'payment_method_types' => ['bancontact', 'card', 'ideal'],
            'payment_method_types' => [ 'card'],

        ]
        );

        return array('client_secret' => $intent->client_secret);
    }

    public function createEphemeralKeys($customer_id){
        $stripe = new \Stripe\StripeClient((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);
        $ephemeralKey = $stripe->ephemeralKeys->create([
            'customer' => $customer_id,
          ], [
            'stripe_version' => '2022-08-01',
          ]);
        return $ephemeralKey;
    }
    public function getPaymentMethods($customer_id, $id = null, $after = null){
        $stripe = new \Stripe\StripeClient(
            (!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST
          );
        if(!empty($id)){
            $result = $stripe->paymentMethods->retrieve(
                $id,
                []
            );

            return $result;
        }

        $stripe = new \Stripe\StripeClient(
            (!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST
          );
        $params = [
            'type' => 'card'
        ];
        if(!empty($after)){
            $params["starting_after"] = $after;
        }
        $result = $stripe->customers->allPaymentMethods(
            $customer_id,
            $params
          );
          return $result;
    }
    public function createPaymentIntent($params, $withConfirmation = true){

        \Stripe\Stripe::setApiKey((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);

        try {
            if($withConfirmation){
                $params['off_session'] = true;
                $params['confirm'] = true;
            }
            return [
                "error" => false,
                "payment_intent" => \Stripe\PaymentIntent::create($params),
            ];
        } catch (\Stripe\Exception\CardException $e) {
            // Error code will be authentication_required if authentication is needed
            return [
                "error" => true,
                "code" => $e->getError()->code,
                "msg" => $e->getError()->message,
                "payment_intent_id" => $e->getError()->payment_intent->id,
                "payment_intent" => \Stripe\PaymentIntent::retrieve($e->getError()->payment_intent->id),
            ];
        }
    }
    public function addPaymentMethod($customerId, $cardParams){
        $stripe = new \Stripe\StripeClient(!defined("STRIPE_SK_TEST") ? STRIPE_SK : STRIPE_SK_TEST);
         $pm = $stripe->paymentMethods->create([
            'type' => 'card',
            'card' => $cardParams,
        ]);
        return $stripe->paymentMethods->attach(
            $pm["id"],
            ['customer' => $customerId]
          );
    }
    public function getCheckoutSession($id){
        $stripe = new \Stripe\StripeClient(
            !defined("STRIPE_SK_TEST") ? STRIPE_SK : STRIPE_SK_TEST
        );
        return $stripe->checkout->sessions->retrieve(
            $id,
            []
        );
    }
    public function getPaymentIntentFromCheckoutSession($id){
        $stripe = new \Stripe\StripeClient(
            !defined("STRIPE_SK_TEST") ? STRIPE_SK : STRIPE_SK_TEST
        );
        return $this->getCheckoutSession($id)["payment_intent"];

    }
    public function createCheckoutSessionInvitado($serviceId, $driverId, $tipo_translado, $amount){
        $stripe = new \Stripe\StripeClient(
            !defined("STRIPE_SK_TEST") ? STRIPE_SK : STRIPE_SK_TEST
        );
        $host = !defined("STRIPE_SK_TEST") ? "https://invitados.kamgus.com" : "https://localhost:4200";
        $checkout_session = $stripe->checkout->sessions->create([
            'line_items' => [[
              'price_data' => [
                'currency' => 'usd',
                'product_data' => [
                  'name' => 'Servicio Invitado',
                  'metadata' => [
                    "service_id" => $serviceId,
                    "service_move_type" => $tipo_translado,
                  ]
                ],
                'unit_amount' => floor($amount * 100),
              ],
              'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $host.'/Thankyou?payment_type=credito&sid='.$serviceId.'&did='. $driverId,
            'cancel_url' => $host.'/summary',
        ]);
        return $checkout_session;
    }
    public function confirmPaymentTest($customer_id, $paymentMethodId, $amount, $fee, $currency = 'usd'){
        if(!defined("STRIPE_SK_TEST")){
            define("STRIPE_SK_TEST", '***REMOVED-STRIPE-TEST***');
        }
        return $this->confirmPayment($customer_id, $paymentMethodId, $amount, $fee, $currency);
    }
    public function createCustomerTest($userId, $name, $lastname, $email, $stripeCustomerId = null){
        if(!defined("STRIPE_SK_TEST")){
            define("STRIPE_SK_TEST", '***REMOVED-STRIPE-TEST***');
        }
        return $this->createCustomer($userId, $name, $lastname, $email, $stripeCustomerId);
    }
    public function createSetupIntentTest($customer_id){
        if(!defined("STRIPE_SK_TEST")){
            define("STRIPE_SK_TEST", '***REMOVED-STRIPE-TEST***');
        }
        return $this->createSetupIntent($customer_id);
    }
    public function createEphemeralKeysTest($customer_id){
        if(!defined("STRIPE_SK_TEST")){
            define("STRIPE_SK_TEST", '***REMOVED-STRIPE-TEST***');
        }
        return $this->createEphemeralKeys($customer_id);
        
    }
    public function getPaymentMethodsTest($customer_id){
        if(!defined("STRIPE_SK_TEST")){
            define("STRIPE_SK_TEST", '***REMOVED-STRIPE-TEST***');
        }
        return $this->getPaymentMethods($customer_id);
        
    }
    public function removePaymentMethodTest($customer_id){
        if(!defined("STRIPE_SK_TEST")){
            define("STRIPE_SK_TEST", '***REMOVED-STRIPE-TEST***');
        }
        return $this->removePaymentMethod($customer_id);
        
    }
    public function confirmPayment($customer_id, $paymentMethodId, $amount, $fee = 0, $currency = 'usd'){
        $params = [
            //'amount' => 1099, //10.99
            'amount' => intval($amount),  //intval devuelve la parte entera del amount
            'currency' => $currency,
            //'application_fee_amount' => intval($fee),
            //'customer' => '{{CUSTOMER_ID}}',
            'customer' => $customer_id,
            //'payment_method' => '{{PAYMENT_METHOD_ID}}',
            'payment_method' => $paymentMethodId,
        ];
        return $this->createPaymentIntent($params, true);
    }

    public function getBalanceTransactions(){
        $stripe = new \Stripe\StripeClient(
            !defined("STRIPE_SK_TEST") ? STRIPE_SK : STRIPE_SK_TEST
        );
        return $stripe->balanceTransactions->all(['limit' => 100, "type" => "payment"]);
    }
}