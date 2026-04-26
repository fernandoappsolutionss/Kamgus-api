<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Replaces Brevo template 256 (RETIRO_FINALIZADO_TEMPLATE).
 */
class RetiroFinalizadoMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $driverName;
    public ?float $amount;
    public ?string $transactionId;
    public ?string $bank;
    public ?string $accountNumber;
    public ?string $date;

    public function __construct(array $params = [])
    {
        $this->driverName    = $params['driverName']    ?? null;
        $this->amount        = isset($params['amount']) ? (float) $params['amount'] : null;
        $this->transactionId = $params['transactionId'] ?? null;
        $this->bank          = $params['bank']          ?? null;
        $this->accountNumber = $params['accountNumber'] ?? null;
        $this->date          = $params['date']          ?? null;
    }

    public function build()
    {
        return $this->subject('Retiro finalizado — Kamgus')
                    ->view('emails.retiro-finalizado');
    }
}
