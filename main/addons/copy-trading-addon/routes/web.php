<?php

use Addons\CopyTrading\App\Http\Controllers\User\CopyHistoryController;
use Addons\CopyTrading\App\Http\Controllers\User\CopyTradingController;
use Addons\CopyTrading\App\Http\Controllers\User\SubscriptionController;
use Addons\CopyTrading\App\Http\Controllers\User\TraderController;
use Illuminate\Support\Facades\Route;

Route::prefix('copy-trading')->name('copy-trading.')->group(function () {
    // Settings
    Route::get('settings', [CopyTradingController::class, 'settings'])->name('settings');
    Route::post('settings', [CopyTradingController::class, 'updateSettings'])->name('settings.update');

    // Browse Traders
    Route::get('traders', [TraderController::class, 'index'])->name('traders.index');
    Route::get('traders/{traderId}', [TraderController::class, 'show'])->name('traders.show');

    // Subscriptions
    Route::get('subscriptions', [SubscriptionController::class, 'index'])->name('subscriptions.index');
    Route::get('subscriptions/create/{traderId}', [SubscriptionController::class, 'create'])->name('subscriptions.create');
    Route::post('subscriptions/{traderId}', [SubscriptionController::class, 'store'])->name('subscriptions.store');
    Route::get('subscriptions/{id}/edit', [SubscriptionController::class, 'edit'])->name('subscriptions.edit');
    Route::put('subscriptions/{id}', [SubscriptionController::class, 'update'])->name('subscriptions.update');
    Route::delete('subscriptions/{id}', [SubscriptionController::class, 'destroy'])->name('subscriptions.destroy');

    // History
    Route::get('history', [CopyHistoryController::class, 'index'])->name('history.index');
});

