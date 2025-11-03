<?php

namespace App\Http\Controllers\OHD;

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
}
