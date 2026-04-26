<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewPasswordReset extends Mailable
{
    use Queueable, SerializesModels;

    public string $textSubject;
    public string $textMessage;
    public string $url_reset;
    /**
     * Create a new message instance.
     * @param string $subject
     * @param string $messaje
     * @return void
     */
    public function __construct(string $subject, string $message, string $url)
    {
        $this->textSubject = $subject;
        $this->textMessage = $message;
        $this->url_reset = $url;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.new.passwordReset', 
        [
            'url' => $this->url_reset,
        ])->subject("Recuperar contraseña " . config("app.name"));;
    }
}
