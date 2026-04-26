<?php
namespace App\Classes;
class PagoCashClass 
{
    //private $restUrl = "https://secure.paguelofacil.com/webservices/rest/regCashTx";
    private $restUrl = "https://sandbox.paguelofacil.com/webservices/rest/regCashTx";
    private $transactionUrl = "https://sandbox.paguelofacil.com/PFManagementServices/api/v1/MerchantTransactions/";
    private $amount = "1.00"; //amount total for transaction
    private $email = "admin@kamgus.com"; //customer email
    private $phone = "+50766666666"; //customer phone
    private $description = "Pay for cash"; //Description for pay
    //private $cclw = "18054582121180545821211805458212118054582121180545821211805458212118054582121"; //Paguelofacil code
    private $cclw = "57BE8E6FB202F3B64CD11C0D56169551361E5ACBDC298A43B174B38CC8E9CAC7FA5793CF372EDE4C34795D576D3F7097D5B6534CADA1227453D3C18668DC3870"; //Paguelofacil code
    private $live_cclw = "150C6E0682694CE1140C0573106FA807E1317F51C1EE3E4FFC63D98E40ED387AD94F9F617137077B0298FF021D77970D5335C1437129B03E3C12B44F553AE8DD"; //Paguelofacil code
    //$cashExpired = 10; //duration in minutes for link payment (ex: 10 minutes)
    private $cashExpired = null;
    private static $instance = null;
    public function __construct(){     
         
        //$this->generateTicket();
                
      
    }
    public function init($amount, $email, $phone, $description){
        $this->amount = $amount;
        $this->email = $email;
        $this->phone = $phone;
        $this->description = $description;
        //$this->cclw = $cclw;
        return $this;
    }
    public function setCashExpire($tMinutes){
        $this->cashExpired = $tMinutes;
    }
    public function getCashExpire(){
        return $this->cashExpired;
    }
    public static function getInstance(){
        if(empty(self::$instance)){
            self::$instance = new PagoCashClass();
        }
        return self::$instance;
    }
    public function generateTicket($depositId, $userId){
        $data = Array(
            "amount" => $this->amount,
            "email" => $this->email,
            "phone" => $this->phone,
            "concept" => $this->description,
            "idUsrRegTx" => null,
            "cclw" => $this->cclw,
            "cashExpired" => $this->getCashExpire(),
            "customFieldValues"  => [
                ["id"=>"idOrder","nameOrLabel"=>"Nro de Orden","value"=>$depositId],
                ["id"=>"idUser","nameOrLabel"=>"User","value"=>$userId],
                //["id"=>"idTx","nameOrLabel"=>"Txtx","value"=>"678643"],
                //["id"=>"reference","nameOrLabel"=>"Referencia","value"=>"6753"],
                //["id"=>"activo","nameOrLabel"=>"estado","value"=>"true"]
            ],
          );
       
       $jsonR = json_encode($data);
       $ch = curl_init ();
       curl_setopt ($ch, CURLOPT_URL,  $this->restUrl);      
       curl_setopt ($ch, CURLOPT_POST, true);
       curl_setopt ($ch, CURLOPT_AUTOREFERER, true);
       curl_setopt ($ch, CURLOPT_FOLLOWLOCATION, true);
       curl_setopt ($ch, CURLOPT_RETURNTRANSFER, true);
       curl_setopt ($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json','Accept: */*'));
       curl_setopt ($ch, CURLOPT_POSTFIELDS, $jsonR);
       
       $result = curl_exec($ch);
                   
       if($result){
           //die($result);
           $result = json_decode($result,true); //transform response in array
           
           if(array_key_exists("code",$result['headerStatus']) == 200){
               //is processed the request
               //ex for response message: 1|PP735886
       
               $data = $result['data'];
               $code = substr($data, 2); //operation code
               $status = substr($data, 0, -9); //status 1|0 -> for the code generation
               
               return $code; // show the code for pagocash
               
           }
           else{
               //dont get a code for paguelofacil
               //show an error message
               die("service error message");
           }
       }
       else{
           //cannot connect with the service in paguelofacil (error message)
           die("connection error message");
       }
    }

    public function saveReturnedInfo($data){
        $code = $data["CodOper"];
    }
}
