<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\OHD\TicketController;

Route::group(['prefix' => 'ticket'], function () {
    Route::get('{ticketId}/pay-now', [TicketController::class, 'payNow'])->name('ticket.pay-now');
});