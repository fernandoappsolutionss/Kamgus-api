<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

/**
 * Replaces Brevo template 219 (RESET_PASSWORD_CAMPAING).
 */
class ResetPasswordMail extends Mailable
{
    use Queueable, SerializesModels;

    public ?string $name;
    public ?string $resetUrl;
    public ?string $code;

    public function __construct(?string $resetUrl = null, ?string $code = null, ?string $name = null)
    {
        $this->name     = $name;
        $this->resetUrl = $resetUrl;
        $this->code     = $code;
    }

    public function build()
    {
        return $this->subject('Restablece tu contraseña — Kamgus')
                    ->view('emails.reset-password');
    }
}
