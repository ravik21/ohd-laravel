<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OHD\TicketController;

Route::group(['prefix' => 'ticket'], function () {
    Route::get('{ticketId}/pay-now', [TicketController::class, 'payNow'])->name('ticket.pay-now');

    Route::group(['prefix' => 'stripe'], function () {
        Route::post('create-customer', [TicketController::class, 'createCustomer'])->name('ticket.stripe.create-customer');
        Route::post('attach-payment-method-to-customer', [TicketController::class, 'attachPaymentMethodToCustomer'])->name('ticket.stripe.attach-payment-method-to-customer');
        Route::post('create-payment-intent', [TicketController::class, 'createPaymentIntent'])->name('ticket.stripe.create-payment-intent');
    });
});