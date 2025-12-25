<?php

use App\Http\Controllers\LogController;
use App\Http\Controllers\MoneyTransferController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| User Routes
|--------------------------------------------------------------------------
|
| User profile, tickets, logs, money transfer, and investment routes.
|
*/

Route::name('user.')->middleware(['auth', 'inactive', 'is_email_verified', '2fa', 'kyc', 'check_onboarding'])->group(function () {
    // User profile routes
    Route::get('profile/setting', [UserController::class, 'profile'])->name('profile');
    Route::post('profile/setting', [UserController::class, 'profileUpdate'])->name('profileupdate');
    Route::get('profile/change/password', [UserController::class, 'changePassword'])->name('change.password');
    Route::post('profile/change/password', [UserController::class, 'updatePassword'])->name('update.password');

    // Ticket routes
    Route::resource('ticket', TicketController::class);
    Route::post('ticket/reply', [TicketController::class, 'reply'])->name('ticket.reply');
    Route::get('ticket/reply/status/change/{id}', [TicketController::class, 'statusChange'])->name('ticket.status-change');
    Route::get('ticket/status/{status}', [TicketController::class, 'ticketStatus'])->name('ticket.status');
    Route::get('ticket/attachement/{id}', [TicketController::class, 'ticketDownload'])->name('ticket.download');

    // Money transfer routes
    Route::get('transfer-money', [MoneyTransferController::class, 'transfer'])->name('transfer_money');
    Route::post('transfer-money', [MoneyTransferController::class, 'transferMoney']);
    Route::get('transfer-money/log', [MoneyTransferController::class, 'transferMoneyLog'])->name('transfer_money.log');
    Route::get('receiver-money/log', [MoneyTransferController::class, 'receiveMoneyLog'])->name('receive_money.log');

    // Investment routes
    Route::get('invest/all', [UserController::class, 'allInvest'])->name('invest.all');
    Route::get('invest/pending', [UserController::class, 'pendingInvest'])->name('invest.pending');
    Route::get('invest/log', [LogController::class, 'investLog'])->name('invest.log');

    // Log routes
    Route::get('transaction/log', [LogController::class, 'transactionLog'])->name('transaction.log');
    Route::get('interest/log', [UserController::class, 'interestLog'])->name('interest.log');
    Route::get('commision', [LogController::class, 'Commision'])->name('commision');
    Route::get('subscription-log', [LogController::class, 'subscriptionLog'])->name('subscription');
    Route::get('refferal', [LogController::class, 'refferalLog'])->name('refferalLog');
});

