<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class K_SendPushNotification extends Notification
{
    use Queueable;

    /**
     * define variables for the notification
    */
    protected $title;
    protected $message;
    protected $fcmTokens;
    protected $data;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($title, $message, $fcmTokens, $data = [])
    {
        $this->title = $title;
        $this->message = $message;
        $this->fcmTokens = $fcmTokens;
        $this->data = $data;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via()
    {
        return [K_FirebaseChannel::class];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toFirebase()
    {
        return [
            "message" => [
                "token" => $this->fcmTokens,
                "notification"=> [
                    "title" => $this->title,
                    "body" => $this->message,
                    "image" => "https://i.ibb.co/JyTv1vY/y-Rs1kg6o-Ja.png",
                ],
                //"content_available" => false,
                "data" => $this->data,
                "webpush" => [
                    "headers" => [
                        "Urgency" => "high"
                    ],
                    "notification"=> [
                        "title" => $this->title,
                        "body" => $this->message,
                        "image" => "https://i.ibb.co/JyTv1vY/y-Rs1kg6o-Ja.png",
                    ],
                    "fcm_options" => [
                        "link" => "https://invitados.kamgus.com",
                    ]
                ],
                "android" => [
                    "notification" => [
                        "sound" => "default",
                        "image" => "https://i.ibb.co/JyTv1vY/y-Rs1kg6o-Ja.png",
                        "icon" => "https://seeklogo.com/images/F/firebase-logo-402F407EE0-seeklogo.com.png"
                    ]
                ],
                "apns" => [
                    "payload" => [
                        "aps" => [
                            "sound" => "default",
                        ]
                    ],
                    "fcm_options" => [
                        "image" => "https://i.ibb.co/JyTv1vY/y-Rs1kg6o-Ja.png",
                        //"icon" => "https://seeklogo.com/images/F/firebase-logo-402F407EE0-seeklogo.com.png"
                    ]
                ]
            ]
        ];
        /*
        return Larafirebase::withTitle($this->title)
        ->withBody($this->message)
        ->withImage('https://i.ibb.co/JyTv1vY/y-Rs1kg6o-Ja.png')
        ->withIcon('https://seeklogo.com/images/F/firebase-logo-402F407EE0-seeklogo.com.png')
        //->withClickAction('admin/notifications')
        ->withPriority('high')
        ->withSound('default')
        ->withAdditionalData($this->data)
        ->sendNotification($this->fcmTokens);
        */
    }

}
