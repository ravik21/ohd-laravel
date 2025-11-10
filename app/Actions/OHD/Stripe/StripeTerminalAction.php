<?php

namespace App\Actions\OHD\Stripe;

use Stripe\PaymentIntent;
use Stripe\Stripe;
use Stripe\Terminal\ConnectionToken;

class StripeTerminalAction
{
    public function connectionToken()
    {
        Stripe::setApiKey(config('cashier.secret'));

        return ConnectionToken::create();
    }

    public function createPaymentIntent($payload)
    {
        Stripe::setApiKey(config('cashier.secret'));

        return PaymentIntent::create([
            'amount' => $payload['amount'] * 100,
            'currency' => 'usd',
            'customer' => $payload['customer_id'],
            'payment_method_types' => ['card_present'],
            'capture_method' => 'manual',
            'setup_future_usage' => 'off_session',
        ]);
    }
}