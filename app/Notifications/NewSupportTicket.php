<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewSupportTicket extends Notification
{
    use Queueable;

    public $id;
    public $title;
    public $description;
    public $image;

    /**
     * Create a new notification instance.
     *
     * @return void
     */
    public function __construct($id, $title, $description, $image)
    {
        //
        $this->id = $id;
        $this->title = $title;
        $this->description = $description;
        $this->image = $image;
    
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
        $mailInstance = (new MailMessage)
                    ->subject("Nuevo ticket")
                    ->greeting('Soporte tecnico!')
                    ->line($this->title)
                    ->line($this->description)
                    //->action('Action', 'https://app.kamgus.com/#/support/'.$this->id)
                    ;
                    if(!empty($this->image)){
                        return $mailInstance->attach(
                            $this->image
                        );
                    }
                    return $mailInstance;
                    //->line('Thank you for using our application!')
                    ;

                      /**
                     *     return (new MailMessage)
                ->greeting('Hello!')
                ->line('One of your invoices has been paid!')
                ->lineIf($this->amount > 0, "Amount paid: {$this->amount}")
                ->action('View Invoice', $url)
                 
                ->line('Thank you for using our application!');
                     */
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
