<?php

use Addons\CopyTrading\App\Http\Controllers\Backend\CopyTradingController;
use Addons\CopyTrading\App\Http\Controllers\Backend\TraderController;
use Illuminate\Support\Facades\Route;

Route::prefix('copy-trading')->name('copy-trading.')->group(function () {
    // Settings
    Route::get('settings', [CopyTradingController::class, 'settings'])->name('settings');
    Route::post('settings', [CopyTradingController::class, 'updateSettings'])->name('settings.update');

    // Traders Management
    Route::get('traders', [TraderController::class, 'index'])->name('traders.index');
    Route::get('traders/{id}', [TraderController::class, 'show'])->name('traders.show');
    Route::post('traders/{id}/toggle', [TraderController::class, 'toggleStatus'])->name('traders.toggle');
});

