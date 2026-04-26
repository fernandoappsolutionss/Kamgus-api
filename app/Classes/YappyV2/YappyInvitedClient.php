<?php
namespace App\Classes\YappyV2;
class YappyInvitedClient implements YappyInterface
{
    private $SUCCESSURL = "";

    const DOMAIN = "https://invitados.kamgus.com";
    private static $instance = null;
    private $url;
    private $merchantId;

    private $token = null;
    private $epochTime = null;
    private $params = [];
    public function __construct() {
        $this->url = config('app.env') == 'production' ? 'https://apipagosbg.bgeneral.cloud' : 'https://api-comecom-uat.yappycloud.com';
        $this->merchantId = config('api.yappy.invited.YAPPY_ID_DEL_COMERCIO');
        $this->SUCCESSURL = url("api/v2/invited/transaction_status/yappy_success");


    }
    public static function getInstance(){
        if(empty(self::$instance)){
            self::$instance = new YappyInvitedClient();
        }
        return self::$instance;
    }
    public function setPaymenParameters($total, $subtotal, $taxes, $orderId, $tel){
        $endpoint = "/payments/validate/merchant";

        $response = $this->request($endpoint, [
            'merchantId' => $this->merchantId,
            'urlDomain' => self::DOMAIN,
        ]);
        $result = json_decode($response, true);
        $this->setToken($result["body"]["token"]);
        $this->setEpochTime($result["body"]["epochTime"]);
        $this->params = [
            'merchantId' => $this->merchantId,
            'orderId' => $orderId,
            'domain' => self::DOMAIN,
            'paymentDate' => $this->getEpochTime(),
            'aliasYappy' => $tel,
            'ipnUrl' => $this->SUCCESSURL,
            'discount' => 0,
            'taxes' => $taxes,
            'subtotal' => $subtotal,
            'total' => $total,
        ];
        
        return $this;
    }
    
    public function startPayment(){
        if($this->getToken() == null){
            return null;
        }
        $response = $this->request('/payments/payment-wc', $this->params, ['Authorization: '.$this->getToken()]);
        $result = json_decode($response, true);
        return [
            'signature' => $result['body']['transactionId'],
            'url' => $result['body']['documentName'],
            'success' => $result['status']['code'] == 200,
        ];
    }
    public function validatePayment(){

    }
    private function setToken($nToken){
        $this->token = $nToken;
    }
    private function getToken(){
        return $this->token;
    }
    private function setEpochTime($nEpochTime){
        $this->epochTime = $nEpochTime;
    }
    private function getEpochTime(){
        return $this->epochTime;
    }
    private function request($endpoint, $params, $headers = []){
        $url = $this->url.$endpoint;
        $curl = curl_init();
        $fields = $params;
        $fields_string = http_build_query($fields);
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_POST, TRUE);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        $data = curl_exec($curl);
        curl_close($curl);
        return $data;
    }
}
