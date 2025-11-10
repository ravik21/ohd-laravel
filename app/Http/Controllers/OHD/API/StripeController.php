<?php

namespace App\Http\Controllers\OHD\API;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Actions\OHD\Stripe\StripeAction;
use App\Actions\OHD\Stripe\StripeTerminalAction;

class StripeController extends Controller
{
    public function retrievePaymentMethod(Request $request)
    {
        $request->validate([
            'payment_method_id' => 'required'
        ]);

        $response = (new StripeAction)->retrievePaymentMethod($request->payment_method_id);

        return response()->json($response, isset($response['error']) ? 422 : 200);
    }

    public function createCustomer(Request $request)
    {
        $response = (new StripeAction)->createOrUpdateCustomer($request->all());

        return response()->json($response, isset($response['error']) ? 422 : 200);
    }

    public function attachPaymentMethodToCustomer(Request $request)
    {
        $response = (new StripeAction)->attachPaymentMethodToCustomer($request->customer_id, $request->payment_method_id);

        return response()->json($response, isset($response['error']) ? 422 : 200);
    }

    public function createPaymentIntent(Request $request)
    {
        $response = (new StripeAction)->createPaymentIntent($request->all());

        return response()->json($response, isset($response['error']) ? 422 : 200);
    }

    public function capturePayment(Request $request, $paymentIntentId)
    {
        $response = (new StripeAction)->capturePayment($paymentIntentId, $request->all());

        return response()->json($response, isset($response['error']) ? 422 : 200);
    }

    public function connectionToken(Request $request)
    {
        $response = (new StripeTerminalAction)->connectionToken($request->all());

        return response()->json($response, isset($response['error']) ? 422 : 200);
    }

    public function createTerminalPaymentIntent(Request $request)
    {
        $response = (new StripeTerminalAction)->createPaymentIntent($request->all());

        return response()->json($response, isset($response['error']) ? 422 : 200);
    }
}
