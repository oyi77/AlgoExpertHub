<?php

use App\Http\Controllers\DepositController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayoutController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Payment Routes
|--------------------------------------------------------------------------
|
| Payment, gateway, deposit, and withdrawal routes.
|
*/

Route::name('user.')->middleware(['auth', 'inactive', 'is_email_verified', '2fa', 'kyc', 'check_onboarding'])->group(function () {
    // Withdraw routes
    Route::get('withdraw', [PayoutController::class, 'withdraw'])->name('withdraw');
    Route::get('withdraw/all', [LogController::class, 'allWithdraw'])->name('withdraw.all');
    Route::get('withdraw/pending', [LogController::class, 'pendingWithdraw'])->name('withdraw.pending');
    Route::get('withdraw/complete', [LogController::class, 'completeWithdraw'])->name('withdraw.complete');
    Route::post('withdraw', [PayoutController::class, 'withdrawCompleted']);
    Route::get('withdraw/fetch/{id}', [PayoutController::class, 'withdrawFetch'])->name('withdraw.fetch');
    Route::get('return/interest', [PayoutController::class, 'returnInterest'])->name('returninterest');

    // Payment gateway routes
    Route::get('gateways/{id}', [PaymentController::class, 'gateways'])->name('gateways');
    Route::post('paynow/{id}', [PaymentController::class, 'paynow'])->name('paynow');
    Route::get('gateways/{id}/details', [PaymentController::class, 'gatewaysDetails'])->name('gateway.details');
    Route::post('gateways/{id}/details', [PaymentController::class, 'gatewayRedirect']);
    Route::any('payment-success/{gateway}', [PaymentController::class, 'paymentSuccess'])->name('payment.success');

    // Crypto payment routes
    Route::match(['get', 'post'], '/payments/crypto/pay', Victorybiz\LaravelCryptoPaymentGateway\Http\Controllers\CryptoPaymentController::class)
        ->name('payments.crypto.pay');
    
    Route::post('/payments/crypto/callback', [\App\Services\Gateway\Gourl::class, 'callback'])
        ->withoutMiddleware(['web', 'auth'])
        ->name('payments.crypto.callback');

    // Deposit routes
    Route::get('deposit', [DepositController::class, 'deposit'])->name('deposit');
    Route::get('deposit/log', [LogController::class, 'depositLog'])->name('deposit.log');
});

