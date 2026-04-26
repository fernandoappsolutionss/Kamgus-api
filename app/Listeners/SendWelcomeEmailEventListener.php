<?php

namespace App\Listeners;

use App\Events\WelcomeEmailEvent;
use App\Mail\NewUser;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\Mail;

class SendWelcomeEmailEventListener
{
    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  \App\Events\WelcomeEmailEvent  $event
     * @return void
     */
    public function handle(WelcomeEmailEvent $event)
    {
        Mail::to($event->textSubject)->send( new NewUser('Motivo', 'Mensaje') );
    }
}
