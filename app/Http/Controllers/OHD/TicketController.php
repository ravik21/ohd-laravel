<?php

namespace App\Http\Controllers\OHD;

use Illuminate\Http\Request;

use App\Http\Controllers\Controller;

use App\Actions\OHD\Ticket\Payment;

use Inertia\Inertia;

class TicketController extends Controller
{
    public function payNow(Request $request, $ticketId)
    {
        $parentUrl    = $request->parentUrl;
        $ticket       = (new Payment())->execute($ticketId);
        $amount       = (new Payment())->amount($ticketId);
        $repairItems  = (new Payment())->repairItems($ticketId);

        return Inertia::render('Stripe/Ticket', [
          'ticket' => $ticket,
          'amount' => $amount,
          'repairItems' => $repairItems,
          'parentUrl' => $parentUrl
        ]);
    }
}
