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

    // ========== BETA ROUTES (React/Inertia UI) ==========
    Route::prefix('beta')->name('beta.')->group(function () {
        // Profile
        Route::get('/profile', [UserController::class, 'betaProfile'])->name('profile');
        Route::post('/profile', [UserController::class, 'betaProfileUpdate'])->name('profile.update');
        Route::get('/profile/change-password', [UserController::class, 'betaChangePassword'])->name('change.password');
        Route::post('/profile/change-password', [UserController::class, 'betaUpdatePassword'])->name('update.password');

        // Tickets
        Route::resource('ticket', TicketController::class)->only(['index', 'create', 'store', 'show', 'update', 'destroy'])->names([
            'index' => 'ticket.index',
            'create' => 'ticket.create',
            'store' => 'ticket.store',
            'show' => 'ticket.show',
            'update' => 'ticket.update',
            'destroy' => 'ticket.destroy',
        ]);
        Route::post('ticket/reply', [TicketController::class, 'betaReply'])->name('ticket.reply');
        Route::get('ticket/status/{status}', [TicketController::class, 'betaTicketStatus'])->name('ticket.status');

        // Wallet
        Route::get('/deposit', [UserController::class, 'betaDeposit'])->name('deposit');
        Route::get('/withdraw', [UserController::class, 'betaWithdraw'])->name('withdraw');
        Route::get('/transfer-money', [MoneyTransferController::class, 'betaTransfer'])->name('transfer_money');
        Route::post('/transfer-money', [MoneyTransferController::class, 'betaTransferMoney']);
        Route::get('/transfer-money/log', [MoneyTransferController::class, 'betaTransferMoneyLog'])->name('transfer_money.log');
        Route::get('/receiver-money/log', [MoneyTransferController::class, 'betaReceiveMoneyLog'])->name('receive_money.log');

        // Logs
        Route::get('/transaction/log', [LogController::class, 'betaTransactionLog'])->name('transaction.log');
        Route::get('/interest/log', [UserController::class, 'betaInterestLog'])->name('interest.log');
        Route::get('/deposit/log', [LogController::class, 'betaDepositLog'])->name('deposit.log');
        Route::get('/refferal', [LogController::class, 'betaRefferalLog'])->name('refferalLog');
        Route::get('/commision', [LogController::class, 'betaCommision'])->name('commision');
        Route::get('/subscription-log', [LogController::class, 'betaSubscriptionLog'])->name('subscription.log');
        Route::get('/plans', [\App\Http\Controllers\PlanController::class, 'betaPlans'])->name('plans');
        Route::get('/subscription', [UserController::class, 'betaSubscription'])->name('subscription');

        // Investment
        Route::get('/invest/all', [UserController::class, 'betaAllInvest'])->name('invest.all');
        Route::get('/invest/pending', [UserController::class, 'betaPendingInvest'])->name('invest.pending');
        Route::get('/invest/log', [LogController::class, 'betaInvestLog'])->name('invest.log');

        // Withdraw History
        Route::get('/withdraw/history', [LogController::class, 'betaAllWithdraw'])->name('withdraw.history');
        Route::get('/withdraw/pending', [LogController::class, 'betaPendingWithdraw'])->name('withdraw.pending');
        Route::get('/withdraw/completed', [LogController::class, 'betaCompleteWithdraw'])->name('withdraw.completed');

        // Additional Beta Routes
        Route::get('/external-signals', [UserController::class, 'betaExternalSignals'])->name('external-signals.index');
        Route::get('/trading/overview', [UserController::class, 'betaTradingOverview'])->name('trading.overview');
        Route::get('/gateways', [UserController::class, 'betaGateways'])->name('gateways');
        Route::post('/gateways/paynow/{id}', [UserController::class, 'betaPaynow'])->name('paynow');

        // Onboarding Routes
        Route::prefix('onboarding')->name('onboarding.')->group(function () {
            Route::get('/welcome', [UserController::class, 'betaOnboardingWelcome'])->name('welcome');
            Route::post('/welcome/complete', [UserController::class, 'betaOnboardingWelcomeComplete'])->name('welcome.complete');
            Route::get('/step/{step?}', [UserController::class, 'betaOnboardingStep'])->name('step');
            Route::post('/step/{step}/complete', [UserController::class, 'betaOnboardingStepComplete'])->name('step.complete');
            Route::get('/complete', [UserController::class, 'betaOnboardingComplete'])->name('complete');
            Route::post('/skip', [UserController::class, 'betaOnboardingSkip'])->name('skip');
        });
    });
});

