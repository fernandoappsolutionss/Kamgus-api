<?php

//Ya quedo estructurado pero falta probarlo
// date_default_timezone_set('America/Bogota');

use App\Classes\StripeCustomClass;
use App\Models\Configuration;

defined('BASEPATH') OR exit('No direct script access allowed');
// hardcoded Stripe key removed; use env('STRIPE_SECRET') / env('STRIPE_SECRET_TEST')
//define("F_ENVIROMENT", 'development');

//require_once( APPPATH.'/libraries/REST_Controller.php');
////importando libreria restful
//require( APPPATH.'/libraries/StripeCustomClass.php');
//include_once( APPPATH.'/libraries/UtilitiesClass.php');


//use Restserver\Libraries\REST_Controller;

class StripeCustomer {

    private $stripeCustomClass = null;
    public function __construct() {
   
        //parent::__construct();
        $this->stripeCustomClass = StripeCustomClass::getInstance();
    } 
    //cus_N3A794oEhorOR1
    //--------------------------	Crhistian L 	---------------------

    //crea un customer asociado al usuario
    public function stripeCustomer_post(){
        $data = $this->post();
        if((!empty($_SERVER["CONTENT_TYPE"])) && strtolower($_SERVER["CONTENT_TYPE"]) == 'application/json'){			
			$data = json_decode(file_get_contents('php://input'), true);
		}
        try {
            $this->stripeCustomClass->createCustomer($this->db, $data['user_id']);
        } catch (\Throwable $th) {
            //throw $th;
            return $this->response([
                "error" => true,
                "msj" => $th->getMessage(),
            ], REST_Controller::HTTP_BAD_GATEWAY);
        }
        return $this->response([
            "error" => false,
            "msj" => "Stripe Customer creado exitosamente"
        ], REST_Controller::HTTP_OK);

    }
    //crea un setup item para agregar un metodo de pago
    public function setupIntent_post(){
        $data = $this->post();
        if((!empty($_SERVER["CONTENT_TYPE"])) && strtolower($_SERVER["CONTENT_TYPE"]) == 'application/json'){			
			$data = json_decode(file_get_contents('php://input'), true);
		}
        $query = $this->db->query('SELECT * FROM customers WHERE idusuarios = "'.$this->db->escape_str($data["user_id"]).'"');
        $user = $query->row();        
        // $this->stripeCustomClass->createSetupIntent($user->StripeCustomerId);
        $response = $this->stripeCustomClass->createSetupIntent($user->StripeCustomerId);
        if(!empty($data["ek"]) && $data["ek"] == 'true'){
            $ephemeralKey = $this->stripeCustomClass->createEphemeralKeys($user->StripeCustomerId);
            $response = [
                "setupIntent" => $response["client_secret"],
                'ephemeralKey' => $ephemeralKey->secret,
                'customer' => $user->StripeCustomerId,
                'publishableKey' => (!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST,
            ];
        }
        $this->response([
            "error" => false,
            "msj" => "Setup intent creado exitosamente",
            "data" => $response,
        ], REST_Controller::HTTP_OK);
	}
    //Crea un payment intent para realizar un proceso de pago
    public function paymentIntent_put(){
        $request = (object) $this->put();
        $userId  = $request->user_id;
        $serviceId  = $request->service_id;
        $paymentMethodId  = $request->payment_method;
        $value  = $request->value; //Este campo es temporal hasta que se ajuste la aplicacion de conductor. por seguridad se debe eliminar y obtener el precio del backend.
        $query = $this->db->query('SELECT * 
			FROM servicios 
			WHERE estado NOT IN("Anulado", "Terminado", "Cancelado", "Repetir")
			AND idservicios = '.$serviceId.' ORDER BY idservicios DESC');
		if( !($query->num_rows() > 0) ){
			$response = array('error' => true, 'msg' => 'Servicio no encontrado' );
			$this->response( $response , REST_Controller::HTTP_NOT_FOUND );	
		}
        //$amount = $query->row()->valor;// Aqui va el monto total del servicio

        /*
            IMPORTANTE: obtener el precio del backend.
        */
        $amount = $value;// Aqui va el monto total del servicio

        $query = $this->db->query('SELECT * FROM customers WHERE idusuarios = "'.$userId.'"');
        $user = $query->row();
        if( $user ){
            try {
                //code...
                $result = $this->stripeCustomClass->confirmPayment($user->StripeCustomerId, $paymentMethodId, $amount * 100);
                if($result["error"]){
                    return $this->response($result, REST_Controller::HTTP_BAD_REQUEST);
                }
                $sql = "UPDATE servicios SET valor = ?, tokenTarjeta = ?, Response = ? WHERE idservicios = ?";//se le asigna el customer id al usuario
                $query = $this->db->query($sql, array(
                    $amount,
                    $result["payment_intent"]->id,
                    $result["payment_intent"]->status,
                    $serviceId
                ));
                $this->notifyServiceStatus($serviceId, true, "Estado del servicio", "Oferta aceptada");                
                
                return $this->response($result, REST_Controller::HTTP_OK);
            } catch (\Stripe\Exception\InvalidRequestException $e) {
                $result = [
                    "error" => true,
                    "amount" => $amount * 100,
                    "msg" => $e->getError()->message
                ];
                return $this->response($result, REST_Controller::HTTP_BAD_REQUEST);

                //throw $th;
            }
		}else{
			$response = array('error' => true, 'msg' => 'Servicio no encontrado' );
			$this->response( $response , REST_Controller::HTTP_NOT_FOUND );	
		}
        
    }
    public function paymentSuccessfully_get(){
        $response = array('error' => false, 'msg' => 'Pago exitoso.' );
		$this->response( $response , REST_Controller::HTTP_OK );	
    }
    //Consulta y devuelve las tarjetas asociadas al conductor
    public function payment_methods_get($id = null){
        $request = $this->get();
        $user_id = $request["user_id"];
        if(!defined("STRIPE_SK_TEST")){
            // hardcoded Stripe key removed; use env('STRIPE_SECRET') / env('STRIPE_SECRET_TEST')
        }
        $query = $this->db->query('SELECT StripeCustomerId
			FROM customers 
			WHERE idusuarios = '.intval($user_id).' LIMIT 1');
        if( $query->num_rows() > 0 ){
            if(!empty($id)){
                $stripe = new \Stripe\StripeClient(
                    (!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST
                  );
                $result = $stripe->paymentMethods->retrieve(
                    $id,
                    []
                  );
                $this->response( array(
                    "data" => [$result], 
                ) , REST_Controller::HTTP_OK );
            }
            //$response = array('error' => false, 'msg' => 'Pago realizado satisfactoriamente', 'articulos'=> $query->result_array());
            //$this->response( $response , REST_Controller::HTTP_OK );
            $customerId = $query->row()->StripeCustomerId;
            if(empty($customerId)){                
                $this->response( array(
                    "error" => true, 
                    "msg" => "CustomerId esta vacio"
                ) , REST_Controller::HTTP_NOT_FOUND );
            }
            $stripe = new \Stripe\StripeClient(
                (!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST
              );
            $params = [
                'type' => 'card'
            ];
            if(!empty($request["after"])){
                $params["starting_after"] = $request["after"];
            }
            $result = $stripe->customers->allPaymentMethods(
                $customerId,
                $params
              );
            $this->response( array(
                "error"=>false,
                "data" => $result["data"], 
                "has_more" => $result["has_more"],
                //"default_pm" => $this->getDefaultPaymentMethodCustomerInfo($customerId)
            ) , REST_Controller::HTTP_OK );
        }else{
            $response = array('error' => true, 'msg' => 'Cliente no encontrado' );
            $this->response( $response , REST_Controller::HTTP_NOT_FOUND );	
        }

    }

    //Elimina un metodo de pago especificado asociado a un conductor
    public function payment_methods_delete($paymentId){
        $response = $this->removePaymentMethod($paymentId);
        $this->response( $response, REST_Controller::HTTP_OK );
    }

    private function notifyServiceStatus($serviceId, $driver, $title, $description){        
        $tokenSQL = "SELECT fcm.token, fcm.created_at 
        FROM fcm_tokens AS fcm 
        INNER JOIN usuarios ON fcm.id_usuario = usuarios.idusuarios 
        INNER JOIN servicios AS s ON s.usuarios_id = fcm.id_usuario 
        WHERE fcm.role_id = 2 AND s.idservicios = ".$this->db->escape_str($serviceId)." order by fcm.created_at DESC limit 1";
        if($driver){
            $tokenSQL = "SELECT fcm.token from fcm_tokens as fcm INNER JOIN usuarios on fcm.id_usuario = usuarios.idusuarios inner join precio_sugerido as ps on ps.usuario_id = usuarios.idusuarios
            WHERE ps.by_driver = 1 and fcm.role_id = 1 and ps.servicio_id = ".$this->db->escape_str($serviceId)."";            
        }
        $query = $this->db->query($tokenSQL);
        $user = $query->row();
        $this->notifyToDriver($user->token, $title, $description);

    }
    private function notifyToDriver($to, $title, $description, $data = []){
        $expoNotification = new ExpoNotifications();
        $expoNotification->notify($to, $title, $description, $data);		
    }
    private function getStripeCustomerId($user_id){
        if(empty($user_id)){
            return false;
        }
        $query = $this->db->query('SELECT StripeCustomerId
			FROM customers 
			WHERE idusuarios = '.intval($user_id).' LIMIT 1');
        if( $query->num_rows() > 0 ){
            $stripeCustomerId = $query->row()->StripeCustomerId;
            return $stripeCustomerId;
        }else{
            return false;
        }
    }

    //Cada usuario  de kamgus usuario debe tener un id de customer
    public function createCustomer($db, $userId){
        if(!defined("STRIPE_SK_TEST")){
            \Stripe\Stripe::setApiKey(STRIPE_SK);
        }
        $q = $db->get_where("customers", [
            "idusuarios" => $userId,
        ]);
        $user = $q->row();
        if(!empty($user->StripeCustomerId)){ //Evalua si el usuario ya tiene un customer asociado
          
            $customer = \Stripe\Customer::update(
                $user->StripeCustomerId, 
                [
                    "name" => implode(", ", [$user->FirstName, $user->LastName]),
                    "email" => $user->Email,
                ]
            ); //crea un nuevo customer
    
           //$query = $this->db->query('UPDATE customers SET StripeCustomerId = "'.$customer->id.'" WHERE idusuarios ='.$user->idusuarios  );//se le asigna el customer id al usuario
            $sql = "UPDATE customers SET StripeCustomerId = ? WHERE idusuarios = ?";//se le asigna el customer id al usuario
            $query = $this->db->query($sql, array($customer->id, $user->idusuarios));
            return $user->StripeCustomerId;
        }
        $userInfo = $this->db->get_where('usuarios', [
            "idusuarios" => $user->idusuarios,
        ]);
        if($userInfo->num_rows() <= 0){
            return -1;
        }
        $userInfo = $userInfo->row();

        $this->db->insert('customers', [
            "UniqueIdentifier" => -1,
            "Email" => $userInfo->email,
            "FirstName" => $userInfo->nombre,
            "LastName" => $userInfo->apellidos,
            "Phone" => $userInfo->celular,            
            "EntityType" => 2,
            "idusuarios" => $userInfo->idusuarios,        
        ]);
        $customer = \Stripe\Customer::create([
            "name" => implode(", ", [$user->FirstName, $user->LastName]),
            "email" => $user->Email,
        ]); //crea un nuevo customer

    	//$query = $this->db->query('UPDATE customers SET StripeCustomerId = "'.$customer->id.'" WHERE idusuarios ='.$user->idusuarios  );//se le asigna el customer id al usuario
        $sql = "UPDATE customers SET StripeCustomerId = ? WHERE idusuarios = ?";//se le asigna el customer id al usuario
        $query = $this->db->query($sql, array($customer->id, $user->idusuarios));
        return $customer->id;
    }
    //Consultar el metodo de pago (tarjeta) por defecto del conductor
    public function getDefaultPaymentMethodCustomerInfo($customerId){
        if(!defined("STRIPE_SK_TEST")){
            \Stripe\Stripe::setApiKey(STRIPE_SK);
        }
        $customer = \Stripe\Customer::retrieve(
            $customerId, 
            []
        );
        return $customer["invoice_settings"]["default_payment_method"];
    }
    //Registra un deposito iniciado por el conductor con Stripe como metodo de pago.
    public function setDepositStripe_post(){
		$data = $this->post();
		if( (!empty($_SERVER["CONTENT_TYPE"])) && strtolower($_SERVER["CONTENT_TYPE"]) == 'application/json'){			
			$data = json_decode(file_get_contents('php://input'), true);
		}
        $pmId =  $data["payment_method_id"];
        $value =  $data["amount"];
        $userId = $data["id"];
        $query = $this->db->query('SELECT * FROM customers WHERE idusuarios = "'.$this->db->escape_str($userId).'"');
        $user = $query->row();   

        try {
            
            $result = $this->confirmPayment($user->StripeCustomerId, $pmId, $value * 100.0);
            /*
            $result = [];
            $result["error"] = false;
            $result["payment_intent"]['id'] =  "pi_test";
            $result["payment_intent"]['status'] =  "succeeded";
            */
            if($result["error"]){
                return $this->response($result, REST_Controller::HTTP_BAD_REQUEST);
            }
            $this->db->insert("balances_conductores", [
                "idusuarios" => $userId,
                "id_role" => 1,
                "valor" => $value,
                "transaction_id" => $result["payment_intent"]['id'],
                "response" => $result["payment_intent"]['status'],
                "gateway_type" => 'Stripe',
                "operation" => '1',
                "fecha" => date("Y-m-d H:i:s"),
                "created_at" => date("Y-m-d H:i:s"),
            ]);
            $userBalance = UtilitiesClass::getInstance()->calculateUserBalance($userId, $this);
            if (round($userBalance, 2) >= Configuration::find(Configuration::BALANCE_MINIMO_ID)->comision ) {
                $this->db->set('estado', '2');
				$this->db->where("idusuarios", $userId );
				$this->db->update('usuarios');
            }
            //code...
            $response = array('error' => false, 'msg' => 'Deposito registrado');
            $this->response( $response , REST_Controller::HTTP_OK );
        } catch (\Exception $th) {
            //throw $th;
            $response = array('error' => true, 'msg' => $th->getMessage(), "amount" => $data["amount"] );
			$this->response( $response , REST_Controller::HTTP_OK );	
        }
            
			
		
	}
    //Confirma un payment intent para finalizar proceso de pago con stripe en modo de prueba.
    public function confirmPaymentTest($customer_id, $paymentMethodId, $amount, $currency = 'usd'){
        define("STRIPE_SK_TEST", ''  /* removed-key */);
        
        return $this->confirmPayment($customer_id, $paymentMethodId, $amount, $currency);
    }
    
    //Permite crear un Setup Intent para un customer especificado
    private function createSetupIntent($customer_id){
        $stripe = new \Stripe\StripeClient((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);

        $intent = $stripe->setupIntents->create(
        [
            'customer' => $customer_id,
            //'payment_method_types' => ['bancontact', 'card', 'ideal'],
            'payment_method_types' => [ 'card'],

        ]
        );

        return $this->response(array('client_secret' => $intent->client_secret));
    }
    
    private function removePaymentMethod($id){
        $stripe = new \Stripe\StripeClient((!defined("STRIPE_SK_TEST")) ? STRIPE_SK : STRIPE_SK_TEST);
        return $stripe->paymentMethods->detach(
            $id,
            []
          );
    }



    //Crea un payment intent que representa un pago
    private function createPaymentIntent($params, $withConfirmation = true){

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
    //Confirma un payment intent para finalizar proceso de pago con stripe en modo de vivo (producción).
    private function confirmPayment($customer_id, $paymentMethodId, $amount, $currency = 'usd'){
        $params = [
            //'amount' => 1099, //10.99
            'amount' => intval($amount),  //intval devuelve la parte entera del amount
            'currency' => $currency,
            //'customer' => '{{CUSTOMER_ID}}',
            'customer' => $customer_id,
            //'payment_method' => '{{PAYMENT_METHOD_ID}}',
            'payment_method' => $paymentMethodId,
        ];
        
        return $this->createPaymentIntent($params, true);//Crea un payment intent para completarse de inmediato.
    }

    

}