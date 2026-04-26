<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class StatusService extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public string $textSubject;
    public string $textMessage;
    public string $status;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct(string $subject, string $message, string $status)
    {
        $this->textSubject = $subject;
        $this->textMessage = $message;
        $this->status = $status;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->markdown('emails.new.active-service')->subject("Estado de servicio");;
    }
}
