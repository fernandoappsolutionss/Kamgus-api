<?php

namespace Tests\Unit;

use App\Classes\YappyV2\YappyInterface;
use App\Classes\YappyV2\YappyInvitedClient;
use PHPUnit\Framework\TestCase;

class YappyTest extends TestCase
{
    /**
     * A basic test example.
     *
     * @return void
     */
    public function testBasicTest()
    {
        $d = YappyInvitedClient::getInstance();
        $amount = 10;
        $tax = 0.7;

        $response = $d->setPaymenParameters($amount + $tax, $amount, $tax, "1000A", "60000002")->startPayment();
        echo json_encode($response);
        $this->assertTrue(true);
    }
}
