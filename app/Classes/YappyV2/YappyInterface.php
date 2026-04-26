<?php
namespace App\Classes\YappyV2;
interface YappyInterface {
    public static function getInstance();
    public function setPaymenParameters($total, $subtotal, $taxes, $orderId, $tel);
    public function startPayment();
    public function validatePayment();
}
