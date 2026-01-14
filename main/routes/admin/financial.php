<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\PaymentController;
use App\Http\Controllers\Backend\ManageDepositController;
use App\Http\Controllers\Backend\ManageWithdrawController;
use App\Http\Controllers\Backend\ManageGatewayController;

/*
|--------------------------------------------------------------------------
| Admin Financial Management Routes
|--------------------------------------------------------------------------
|
| Payment, deposit, withdrawal, and gateway management routes.
| Requires appropriate permissions (manage-gateway, manage-deposit, manage-withdraw).
|
*/

Route::middleware(['web', 'admin', 'demo'])->prefix('admin')->name('admin.')->group(function () {
    
    // Payment Management
    Route::middleware('permission:payments,admin')->prefix('payments')->name('payments.')->group(function () {
        Route::get('/{type}', [PaymentController::class, 'payment'])->name('index');
        Route::get('/details/{id}', [PaymentController::class, 'details'])->name('details');
        Route::post('/accept/{trx}', [PaymentController::class, 'accept'])->name('accept');
        Route::post('/reject/{trx}', [PaymentController::class, 'reject'])->name('reject');
    });
    
    // Payment Gateways (Legacy route naming for compatibility)
    Route::middleware('permission:manage-gateway,admin')->prefix('payment')->name('payment.')->group(function () {
        Route::get('/', [ManageGatewayController::class, 'online'])->name('index');
        Route::get('/offline', [ManageGatewayController::class, 'offline'])->name('offline');
        Route::get('/{view}', [ManageGatewayController::class, 'loadView'])->name('view');
        Route::post('/status/{id}', [ManageGatewayController::class, 'status'])->name('status');
        Route::put('/online/{id}', [ManageGatewayController::class, 'updateOnlinePaymentGateway'])->name('online.update');
        Route::post('/gourl', [ManageGatewayController::class, 'gourlUpdate'])->name('gourl.update');
        Route::get('/offline/create', [ManageGatewayController::class, 'offlineCreate'])->name('offline.create');
        Route::post('/offline', [ManageGatewayController::class, 'offlineStore'])->name('offline.store');
        Route::get('/offline/{id}/edit', [ManageGatewayController::class, 'offlineEdit'])->name('offline.edit');
        Route::put('/offline/{id}', [ManageGatewayController::class, 'offlineUpdate'])->name('offline.update');
    });
    
    // Deposit Management
    Route::middleware('permission:manage-deposit,admin')->group(function () {
        Route::get('/deposit/log/{user?}', [\App\Http\Controllers\Backend\LogController::class, 'depositLog'])->name('deposit.log');
        Route::get('/deposit/{status}', [ManageDepositController::class, 'index'])->name('deposit');
        Route::post('/deposit/{trx}/accept', [ManageDepositController::class, 'accept'])->name('deposit.accept');
        Route::post('/deposit/{trx}/reject', [ManageDepositController::class, 'reject'])->name('deposit.reject');
        Route::get('/deposit/{trx}/details', [ManageDepositController::class, 'details'])->name('deposit.details');
    });
    
    // Withdraw Management
    Route::middleware('permission:manage-withdraw,admin')->prefix('withdraw')->name('withdraw.')->group(function () {
        Route::get('/', [ManageWithdrawController::class, 'index'])->name('index');
        Route::post('/method', [ManageWithdrawController::class, 'withdrawMethodCreate'])->name('method.create');
        Route::put('/method', [ManageWithdrawController::class, 'withdrawMethodUpdate'])->name('method.update');
        Route::delete('/method', [ManageWithdrawController::class, 'withdrawMethodDelete'])->name('method.delete');
        Route::get('/filter/{status?}', [ManageWithdrawController::class, 'filterWithdraw'])->name('filter');
        Route::post('/{withdraw}/accept', [ManageWithdrawController::class, 'withdrawAccept'])->name('accept');
        Route::post('/{withdraw}/reject', [ManageWithdrawController::class, 'withdrawReject'])->name('reject');
        Route::get('/log/{id}', [ManageWithdrawController::class, 'withdrawLog'])->name('log');
    });
});
