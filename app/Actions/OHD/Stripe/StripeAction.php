<?php

namespace App\Actions\OHD\Stripe;

use Stripe\Stripe;
use Stripe\Customer;
use Stripe\PaymentIntent;
use Stripe\PaymentMethod;

class StripeAction
{
    public function createOrUpdateCustomer($payload)
    {
        Stripe::setApiKey(config('cashier.secret'));

        $validation = $this->validatePayload($payload);

        if ($validation['success']) {
            $customerId = $this->checkIfCustomerExists($payload);

            $customerPayload = $this->filterCustomerStripePayload($payload);

            try {
                if ($customerId) {
                    return Customer::update($customerId, $customerPayload);
                } else {
                    return Customer::create($customerPayload);
                }
            } catch (\Exception $e) {
                return ['error' => $e->getMessage(), 'code' => 400, 'success' => false];
            }
        } else {
            return $validation;
        }
    }

    public function attachPaymentMethodToCustomer($customerId, $paymentMethodId)
    {
        try {
            Stripe::setApiKey(config('cashier.secret'));

            // Attach the payment method to the customer
            $attachedPaymentMethod = PaymentMethod::retrieve($paymentMethodId);
            $attachedPaymentMethod->attach(['customer' => $customerId]);

            // Optionally set as default payment method
            Customer::update($customerId, [
                'invoice_settings' => [
                    'default_payment_method' => $paymentMethodId,
                ],
            ]);

            return $attachedPaymentMethod;
        } catch (\Exception $e) {
            return ['error' => $e->getMessage(), 'code' => 400, 'success' => false];
        }
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

    public function retrievePaymentMethod($paymentMethodId)
    {
        Stripe::setApiKey(config('cashier.secret'));

        return PaymentMethod::retrieve($paymentMethodId);
    }

    public function capturePayment($paymentIntentId, $payload)
    {
        Stripe::setApiKey(config('cashier.secret'));

        if (empty($payload['amount_to_capture'])) {
            return ['error' => "Missing required field: amount", 'code' => 400, 'success' => false];
        }

        if (!is_numeric($payload['amount_to_capture']) || intval($payload['amount_to_capture']) <= 0) {
            return ['error' => "Amount must be a positive number.", 'code' => 400, 'success' => false];
        }

        return PaymentIntent::retrieve($paymentIntentId)->capture($payload);
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

        $search = Customer::search([
            'query' => $query,
        ]);

        if (!empty($search->data) && isset($search->data[0]->id)) {
            return $search->data[0]->id;
        }

        return false;
    }

    protected function filterCustomerStripePayload($payload)
    {
        $filteredPayload = [
            'email' => $payload['email'],
            'name' => $payload['name'],
            'phone' => $payload['phone'],
            'metadata' => [
                'ticket_id' => $payload['ticket_id'],
                'ticket_num' => $payload['ticket_num'],
            ],
        ];

        // Optional address fields
        if (!empty($payload['address']) && is_array($payload['address'])) {
            foreach ($payload['address'] as $key => $value) {
                if (!empty($value)) {
                    $filteredPayload['address'][$key] = $value;
                }
            }
        }

        // Optional shipping fields
        if (!empty($payload['shipping']) && is_array($payload['shipping'])) {
            if (!empty($payload['shipping']['name'])) {
                $filteredPayload['shipping']['name'] = $payload['shipping']['name'];
            }

            if (!empty($payload['shipping']['address']) && is_array($payload['shipping']['address'])) {
                foreach ($payload['shipping']['address'] as $key => $value) {
                    if (!empty($value)) {
                        $filteredPayload['shipping']['address'][$key] = $value;
                    }
                }
            }

            // Remove shipping if address is not set or is empty
            if (isset($filteredPayload['shipping']) && !isset($filteredPayload['shipping']['address'])) {
                unset($filteredPayload['shipping']);
            } elseif (isset($filteredPayload['shipping']['address']) && !count($filteredPayload['shipping']['address'])) {
                unset($filteredPayload['shipping']);
            }
        }

        return $filteredPayload;
    }
}
