<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class RequestTransaction extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;
    protected $value;
    protected $driverName;
    protected $transactionId;
    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($value, $driverName, $transactionId)
    {
        //
        $this->value = $value;
        $this->driverName = $driverName;
        $this->transactionId = $transactionId;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.driver.request_transaction', [
            "title"=> "Solicitud de transacción",
            "description" => "Solicitud de retiro",
            "value" => $this->value,
            "driverName" => $this->driverName,
            "transactionId" => $this->transactionId,
        ])->subject("Solicitud de transacción");
    }
}
