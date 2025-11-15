<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OHD\API\StripeController;

Route::group(['prefix' => 'stripe'], function () {
    Route::get('retrieve-payment-method', [StripeController::class, 'retrievePaymentMethod'])->name('stripe.retrieve-payment-method');
    Route::post('create-customer', [StripeController::class, 'createCustomer'])->name('stripe.create-customer');
    Route::post('attach-payment-method-to-customer', [StripeController::class, 'attachPaymentMethodToCustomer'])->name('stripe.attach-payment-method-to-customer');
    Route::post('create-payment-intent', [StripeController::class, 'createPaymentIntent'])->name('stripe.create-payment-intent');
    Route::post('create-terminal-payment-intent', [StripeController::class, 'createTerminalPaymentIntent'])->name('stripe.create-terminal-payment-intent');
    Route::post('{paymentIntentId}/capture-payment', [StripeController::class, 'capturePayment'])->name('stripe.capture-payment');
    Route::post('connection-token', [StripeController::class, 'connectionToken'])->name('stripe.connection-token');
});
