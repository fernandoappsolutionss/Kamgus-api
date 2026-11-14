<?php

namespace Tests\Unit;

use App\Classes\PriceCalculator;
use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    /**
     * @dataProvider realTariffProvider
     */
    public function testSuggestedPriceMatchesTheTotalPriceSentByTheCompiledSpa($name, $minutePrice, $assistantPrice, $serviceTime, $expected)
    {
        $calculator = new PriceCalculator();

        $actual = $calculator->suggestedPrice(1800, 12000, $minutePrice, $assistantPrice, $serviceTime, 0.5);

        $this->assertSame($expected, $actual, $name);
    }

    /**
     * @dataProvider listPreviewTariffProvider
     */
    public function testListPreviewPriceMatchesCalculatePriceByVehiculeFromTheCompiledSpa($name, $minutePrice, $assistantPrice, $serviceTime, $expected)
    {
        $calculator = new PriceCalculator();

        $actual = $calculator->listPreviewPrice(1800, $minutePrice, $assistantPrice, $serviceTime);

        $this->assertSame($expected, $actual, $name);
    }

    public function testPriceBandsMatchTheCompiledSpaThresholds()
    {
        $calculator = new PriceCalculator();

        $this->assertSame('red', $calculator->priceBand(70.0, 100.0));
        $this->assertSame('yellow', $calculator->priceBand(70.01, 100.0));
        $this->assertSame('yellow', $calculator->priceBand(85.99, 100.0));
        $this->assertSame('green', $calculator->priceBand(86.0, 100.0));
    }

    public function realTariffProvider()
    {
        return [
            'Panel 0.47/15/1' => ['Panel', 0.47, 15.00, 1, 63.3],
            'Pick up 0.45/15/1' => ['Pick up', 0.45, 15.00, 1, 61.5],
            'Camion Pequeno 0.75/30/2' => ['Camion Pequeno', 0.75, 30.00, 2, 148.5],
            'Camion Grande 0.85/40/2' => ['Camion Grande', 0.85, 40.00, 2, 173.5],
            'Moto 0.05/3.50/1' => ['Moto', 0.05, 3.50, 1, 14.0],
            'Sedan 0.08/5/1' => ['Sedan', 0.08, 5.00, 1, 18.2],
        ];
    }

    public function listPreviewTariffProvider()
    {
        return [
            'Panel 0.47/15/1' => ['Panel', 0.47, 15.00, 1, 71.2],
            'Pick up 0.45/15/1' => ['Pick up', 0.45, 15.00, 1, 69.0],
            'Camion Pequeno 0.75/30/2' => ['Camion Pequeno', 0.75, 30.00, 2, 165.0],
            'Camion Grande 0.85/40/2' => ['Camion Grande', 0.85, 40.00, 2, 193.0],
            'Moto 0.05/3.50/1' => ['Moto', 0.05, 3.50, 1, 9.0],
            'Sedan 0.08/5/1' => ['Sedan', 0.08, 5.00, 1, 14.8],
        ];
    }
}
