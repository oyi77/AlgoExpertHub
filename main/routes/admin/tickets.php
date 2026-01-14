<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\TicketController;

/*
|--------------------------------------------------------------------------
| Admin Ticket Management Routes
|--------------------------------------------------------------------------
|
| Support ticket management routes. Requires manage-ticket permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-ticket,admin'])->prefix('admin/ticket')->name('admin.ticket.')->group(function () {
    Route::get('/', [TicketController::class, 'index'])->name('index');
    Route::get('/status/{status}', [TicketController::class, 'filterByStatus'])->name('status');
    Route::get('/{id}', [TicketController::class, 'show'])->name('show');
    Route::delete('/{id}', [TicketController::class, 'destroy'])->name('destroy');
    Route::post('/reply', [TicketController::class, 'reply'])->name('reply');
});
