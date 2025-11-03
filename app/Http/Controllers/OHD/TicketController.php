<?php

namespace App\Http\Controllers\OHD;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Actions\OHD\Ticket\PaymentAction;
use App\Actions\OHD\Stripe\StripeAction;

use Inertia\Inertia;

class TicketController extends Controller
{
    public function payNow(Request $request, $ticketId)
    {
        $parentUrl    = $request->parentUrl;
        $ticket       = (new PaymentAction)->execute($ticketId);
        $amount       = (new PaymentAction)->amount($ticketId);
        $repairItems  = (new PaymentAction)->repairItems($ticketId);

        return Inertia::render('Stripe/Ticket', [
          'ticket' => $ticket,
          'amount' => $amount,
          'repairItems' => $repairItems,
          'parentUrl' => $parentUrl
        ]);
    }

    public function paymentCancel()
    {
        return Inertia::render('Stripe/Cancel');
    }

    public function retrievePaymentMethod(Request $request, $ticketId)
    {
        $response = (new StripeAction)->retrievePaymentMethod($request->all(), $ticketId);

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
}
