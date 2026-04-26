<?php

namespace App\Notifications;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
//custom
use Illuminate\Support\Facades\Lang;
use Illuminate\Auth\Notifications\ResetPassword;

class ForgotPasswordNotification extends Notification
{
    use Queueable;
    protected $pageUrl;
    public $token;
    public $driverInfo;
    public $user;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($driver, $user)
    {
        $this->driverInfo = $driver;
        $this->user = $user;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param  mixed  $notifiable
     * @return array
     */
    public function via($notifiable)
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     *
     * @param  mixed  $notifiable
     * @return \Illuminate\Notifications\Messages\MailMessage
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)->view("emails.driver.forgot", [
            "row" => $this->driverInfo->first(),
            "data" => $this->user->toArray(),
            "resetToken" => encrypt(json_encode(["email" => $this->user->email, "exp" => now()->addMinutes(5)]))
        ]);
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
