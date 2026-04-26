<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class NewDriver extends Mailable
{
    use Queueable, SerializesModels;

    public string $textSubject;
    public string $textMessage;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $subject, string $message)
    {
        $this->textSubject = $subject;
        $this->textMessage = $message;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.new.driver')->subject("Bienvenido a " . config("app.name"));
    }
}
