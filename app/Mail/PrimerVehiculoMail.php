<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Replaces Brevo template 269 (PRIMER_VEHICULO_TEMPLATE).
 */
class PrimerVehiculoMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $driverName;
    public ?string $vehicleUrl;

    public function __construct(?string $vehicleUrl = null, ?string $driverName = null)
    {
        $this->vehicleUrl = $vehicleUrl;
        $this->driverName = $driverName;
    }

    public function build()
    {
        return $this->subject('Vehículo registrado — Kamgus')
                    ->view('emails.primer-vehiculo');
    }
}
