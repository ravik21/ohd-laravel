<?php

namespace App\Http\Controllers\OHD;

use App\Actions\OHD\Stripe\StripeAction;
use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Actions\OHD\Ticket\PaymentAction;

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

    public function payTerminal(Request $request, $ticketId)
    {
        $ticket       = (new PaymentAction)->execute($ticketId);
        $amount       = (new PaymentAction)->amount($ticketId);
        $repairItems  = (new PaymentAction)->repairItems($ticketId);

        $ticket['amount'] = $amount;

        $customer     = (new StripeAction)->createOrUpdateCustomer($ticket);

        return Inertia::render('Stripe/Terminal', [
          'ticketId' => $ticket['ticket_id'],
          'amount' => $amount,
          'customerId' => $customer['id'] ?? null,
          'repairItems' => $repairItems
        ]);
    }
}
