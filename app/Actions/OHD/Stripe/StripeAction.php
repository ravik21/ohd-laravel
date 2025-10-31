<?php

namespace App\Actions\OHD\Stripe;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;

class StripeAction
{
    public function createOrUpdateCustomer($payload)
    {
        Stripe::setApiKey(config('cashier.secret'));

        $validation = $this->validatePayload($payload);

        if ($validation['success']) {
            $customerId = $this->checkIfCustomerExists($payload);

            if ($customerId) {
                return Customer::update($customerId, $payload);
            } else {
                return Customer::create($payload);
            }
        } else {
            return $validation;
        }
    }

    public function attachPaymentMethodToCustomer($customerId, $paymentMethod)
    {
        Stripe::setApiKey(config('cashier.secret'));

        return Customer::update($customerId, ['payment_method' => $paymentMethod]);
    }

    public function createPaymentIntent($payload)
    {
        Stripe::setApiKey(config('cashier.secret'));

        $requiredFields = ['customer_id', 'amount', 'payment_method_id'];

        foreach ($requiredFields as $field) {
            if (empty($payload[$field])) {
                return ['error' => "Missing required field: $field", 'code' => 400, 'success' => false];
            }
        }

        return PaymentIntent::create([
            'amount' => $payload['amount'] * 100,
            'currency' => 'usd',
            'customer' => $payload['customer_id'],
            'payment_method' => $payload['payment_method_id'],
            'payment_method_types' => ['card'],
            'capture_method' => 'manual',
        ]);
    }

    protected function validatePayload($payload)
    {
        $requiredFields = ['name', 'email', 'ticket_id', 'ticket_num'];

        foreach ($requiredFields as $field) {
            if (empty($payload[$field])) {
                return ['error' => "Missing required field: $field", 'code' => 400, 'success' => false];
            }
        }

        if (!is_numeric($payload['amount']) || intval($payload['amount']) <= 0) {
            return ['error' => "Amount must be a positive number.", 'code' => 400, 'success' => false];
        }

        return ['success' => true, 'code' => 200];
    }

    protected function checkIfCustomerExists($payload)
    {
        Stripe::setApiKey(config('cashier.secret'));

        $query = 'email:"' . $payload['email'] . '" AND metadata[\'ticket_num\']:"' . $payload['ticket_num'] . '"';

        return Customer::search($query);
    }
}
