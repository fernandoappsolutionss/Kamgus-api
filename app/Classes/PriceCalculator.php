<?php

namespace App\Classes;

final class PriceCalculator
{
    const DEFAULT_PK = 0.5;
    const BAND_RED_MAX = 0.7;
    const BAND_GREEN_MIN = 0.86;

    /*
     * Source of truth: /repos/kamgus-invitados-bridge/build/main.js.
     *
     * List preview formula:
     *   calculatePriceByVehicule(e,t,n,o){const i=Math.round(parseInt(e)/60*2);
     *   return Math.round(i*t)+parseInt(n)+60*o*t}
     *   getVehicles(){... e.precioTotal=this._sh.calculatePriceByVehicule(
     *   this.service.time.duration.value,e.precio_minuto,e.precio_ayudante,e.tiempo)}
     *
     * Sent precio_sugerido formula:
     *   cotizaVehicle(){... const e=parseInt(this.service.time.duration.value)/60;
     *   const t=parseFloat(this.selVehicle.precio_minuto);
     *   const n=parseFloat(this.selVehicle.precio_ayudante); const o=this.selVehicle.pk;
     *   const i=parseFloat(this.service.time.distance.value)/1e3;
     *   const r=parseFloat(this.selVehicle.tiempo); ... this.totalPrice=(e+60*r)*t+i*o+n}
     *   sendArticulos(){... precio_sugerido:this.totalPrice ...}
     *
     * The endpoint returns the sent precio_sugerido, not the preliminary
     * precioTotal shown in the vehicle list.
     */
    public function suggestedPrice($durationSeconds, $distanceMeters, $minutePrice, $assistantPrice, $serviceTime, $pk = self::DEFAULT_PK)
    {
        $durationMinutes = $this->jsParseInt($durationSeconds) / 60;
        $pricePerMinute = $this->jsParseFloat($minutePrice);
        $assistant = $this->jsParseFloat($assistantPrice);
        $distanceKm = $this->jsParseFloat($distanceMeters) / 1000;
        $serviceTime = $this->jsParseFloat($serviceTime);
        $pk = $this->jsParseFloat($pk);

        return $this->normalizeNumber(($durationMinutes + (60 * $serviceTime)) * $pricePerMinute + ($distanceKm * $pk) + $assistant);
    }

    public function listPreviewPrice($durationSeconds, $minutePrice, $assistantPrice, $serviceTime)
    {
        $durationRoundTrip = $this->jsRound($this->jsParseInt($durationSeconds) / 60 * 2);
        $pricePerMinute = $this->jsParseFloat($minutePrice);
        $assistant = $this->jsParseInt($assistantPrice);
        $serviceTime = $this->jsParseFloat($serviceTime);

        return $this->normalizeNumber($this->jsRound($durationRoundTrip * $pricePerMinute) + $assistant + (60 * $serviceTime * $pricePerMinute));
    }

    public function priceBand($clientPrice, $suggestedPrice)
    {
        $suggestedPrice = $this->jsParseFloat($suggestedPrice);
        if ($suggestedPrice <= 0) {
            return null;
        }

        $clientPrice = $this->jsParseFloat($clientPrice);

        if ($clientPrice <= self::BAND_RED_MAX * $suggestedPrice) {
            return 'red';
        }

        if ($clientPrice < self::BAND_GREEN_MIN * $suggestedPrice) {
            return 'yellow';
        }

        return 'green';
    }

    private function jsParseInt($value)
    {
        return (int) $value;
    }

    private function jsParseFloat($value)
    {
        return (float) $value;
    }

    private function jsRound($value)
    {
        return floor($value + 0.5);
    }

    private function normalizeNumber($value)
    {
        $value = round($value, 10);

        return $value == 0.0 ? 0.0 : $value;
    }
}
