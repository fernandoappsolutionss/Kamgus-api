<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use NotificationChannels\ExpoPushNotifications\ExpoChannel;
use NotificationChannels\ExpoPushNotifications\ExpoMessage;

class SendExpoPushNotification extends Notification
{
    use Queueable;
    private $title;
    private $body;
    private $data;
    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title, $body, $data = [])
    {
        //
        $this->title = $title;
        $this->body = $body;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return [ExpoChannel::class];
    }

    public function toExpoPush($notifiable)
    {
        return ExpoMessage::create()
            ->badge(1)
            ->enableSound()
            ->title($this->title)
            ->setJsonData($this->data)//: Accepts a json string or an array for additional.
            //->channelID('') //Accepts a string to set the channelId of the notification for Android devices.
            //->priority('default') //Accepts a string to set the priority of the notification, must be one of [default, normal, high].
            //->ttl(60)
            ->body($this->body);
            
    }

    /**
     * Get the array representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function toArray($notifiable)
    {
        return [
            //
        ];
    }
}
