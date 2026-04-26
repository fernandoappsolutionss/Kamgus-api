<?php

namespace App\Listeners;

use Illuminate\Support\Facades\Log;
use Laravel\Cashier\Events\WebhookReceived;
use App\Models\Transaction;

class StripeEventListener
{
    /**
     * Handle received Stripe webhooks.
     *
     * @param  \Laravel\Cashier\Events\WebhookReceived  $event
     * @return void
     */
    public function handle(WebhookReceived $event)
    {
        if ($event->payload['type'] === 'invoice.payment_succeeded') {
            Log::debug("Funciona!");
        }

        if ($event->payload['type'] === 'charge.succeeded') {
            Log::warning("Funciona!");
        }

        if ($event->payload['type'] === 'charge.pending') {
            Log::warning("Funciona!");
        }

        if ($event->payload['type'] === 'charge.failed') {
            Log::warning("Funciona!");
        }
    }
}
