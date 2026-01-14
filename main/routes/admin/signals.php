<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Backend\SignalController;
use App\Http\Controllers\Backend\MarketController;
use App\Http\Controllers\Backend\SignalCurrencyPairController;
use App\Http\Controllers\Backend\SignalTimeFrameController;

/*
|--------------------------------------------------------------------------
| Admin Signal Management Routes
|--------------------------------------------------------------------------
|
| Signal, market, currency pair, and timeframe management routes.
| Requires signal permission.
|
*/

Route::middleware(['web', 'admin', 'demo', 'permission:signal,admin'])->prefix('admin')->name('admin.')->group(function () {
    
    // Currency Pairs
    Route::resource('currency-pair', SignalCurrencyPairController::class);
    Route::post('currency-pair/changeStatus/{id}', [SignalCurrencyPairController::class, 'changeStatus'])->name('currency-pair.changestatus');
    
    // Markets
    Route::resource('markets', MarketController::class);
    Route::post('markets/changeStatus/{id}', [MarketController::class, 'changeStatus'])->name('markets.changestatus');
    
    // Time Frames
    Route::resource('frames', SignalTimeFrameController::class);
    Route::post('frames/changeStatus/{id}', [SignalTimeFrameController::class, 'changeStatus'])->name('frames.changestatus');
    
    // Signals
    Route::resource('signals', SignalController::class);
    Route::post('signals/send/{id}', [SignalController::class, 'sent'])->name('signals.sent');
    
    // Channel Signals (Auto-Created) - Only register if addon is active
    if (\App\Support\AddonRegistry::active('multi-channel-signal-addon') && \App\Support\AddonRegistry::moduleEnabled('multi-channel-signal-addon', 'admin_ui')) {
        Route::prefix('channel-signals')->name('channel-signals.')->group(function () {
            Route::get('/', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'index'])->name('index');
            Route::get('/{id}', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'show'])->name('show');
            Route::get('/{id}/edit', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'edit'])->name('edit');
            Route::post('/{id}', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'update'])->name('update');
            Route::post('/{id}/approve', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'approve'])->name('approve');
            Route::post('/{id}/reject', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'reject'])->name('reject');
            Route::post('/bulk/approve', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'bulkApprove'])->name('bulk.approve');
            Route::post('/bulk/reject', [\Addons\MultiChannelSignalAddon\App\Http\Controllers\Backend\ChannelSignalController::class, 'bulkReject'])->name('bulk.reject');
        });
    }
});
