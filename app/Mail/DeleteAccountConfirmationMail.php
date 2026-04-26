<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Replaces Brevo template 270 (DELETE_ACCOUNT_CONFIRMATION).
 */
class DeleteAccountConfirmationMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $name;
    public ?string $confirmUrl;
    public ?string $code;

    public function __construct(?string $confirmUrl = null, ?string $code = null, ?string $name = null)
    {
        $this->name       = $name;
        $this->confirmUrl = $confirmUrl;
        $this->code       = $code;
    }

    public function build()
    {
        return $this->subject('Confirma la eliminación de tu cuenta — Kamgus')
                    ->view('emails.delete-account');
    }
}
