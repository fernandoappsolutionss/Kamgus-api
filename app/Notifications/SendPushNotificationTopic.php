<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use GGInnovative\Larafirebase\Facades\Larafirebase;
class SendPushNotificationTopic extends Notification
{
    use Queueable;
    protected $title;
    protected $message;
    protected $topic;
    protected $data;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title, $message, $topic, $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->topic = $topic;
        $this->data = $data;
        //
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['firebase'];
    }

    public function toFirebase()
    {
        return Larafirebase::withTitle($this->title)
        ->withBody($this->message)
        ->withImage('https://i.ibb.co/JyTv1vY/y-Rs1kg6o-Ja.png')
        ->withAdditionalData($this->data)
        ->withToken($this->topic)
        ->sendNotification();
    }
}
