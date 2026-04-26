<?php
namespace App\Classes\YappyV2;
class YappyClient implements YappyInterface
{
    private static $instance = null;
    private $url;
    public function __construct() {
        $this->url = config('app.env') == 'production' ? 'https://apipagosbg.bgeneral.cloud' : 'https://api-comecom-uat.yappycloud.com';
    }
    public static function getInstance(){
        if(empty(self::$instance)){
            self::$instance = new YappyClient();
        }
        return self::$instance;
    }
    public function setPaymenParameters($total, $subtotal, $taxes, $orderId, $tel){
        $merchantId = config('api.yappy.');
    }
    public function startPayment(){

    }
    public function validatePayment(){

    }
}
