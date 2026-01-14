<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\LogController;

/*
|--------------------------------------------------------------------------
| Admin Log Management Routes
|--------------------------------------------------------------------------
|
| Financial and activity log routes. Requires manage-logs permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:manage-logs,admin'])->prefix('admin')->name('admin.')->group(function () {
    // Transaction Log
    Route::get('/transaction-log/{user?}', [LogController::class, 'transaction'])->name('transaction');
    
    // Payment Report
    Route::get('/payment-report/{user?}', [LogController::class, 'paymentReport'])->name('payment.report');
    
    // Withdraw Report
    Route::get('/withdarw-report/{user?}', [LogController::class, 'withdarawReport'])->name('withdraw.report');
    
    // Transfer Log
    Route::get('/transfer/log', [LogController::class, 'transferLog'])->name('transfer.report');
    
    // Commission Log
    Route::get('/commision/{user?}', [LogController::class, 'Commision'])->name('commision');
    
    // Trade Log
    Route::get('/trade-log/{user?}', [LogController::class, 'tradeLog'])->name('trade');
    
    // Deposit Log (also in financial.php but keeping here for consistency)
    Route::get('/deposit/log/{user?}', [LogController::class, 'depositLog'])->name('deposit.log');
});
