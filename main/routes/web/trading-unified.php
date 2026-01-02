<?php

/*
|--------------------------------------------------------------------------
| Unified Trading Pages
|--------------------------------------------------------------------------
|
| Unified trading pages with tab-based navigation including multi-channel
| signals, operations, configuration, backtesting, and marketplaces.
|
*/

// Unified Trading Pages
Route::prefix('trading')->name('trading.')->group(function () {
    // Multi-Channel Signal (unified page with tabs)
    Route::prefix('multi-channel-signal')->name('multi-channel-signal.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\Trading\MultiChannelSignalController::class, 'index'])->name('index');
    });

    // Trading Operations (unified page with tabs)
    Route::prefix('operations')->name('operations.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\Trading\TradingOperationsController::class, 'index'])->name('index');
    });

    // Execution Log (sub menu from Trading Operations)
    Route::prefix('execution-log')->name('execution-log.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\Trading\ExecutionLogController::class, 'index'])->name('index');
        Route::post('manual-trade', [\App\Http\Controllers\User\Trading\ExecutionLogController::class, 'manualTrade'])->name('manual-trade');
        Route::post('position/{id}/close', [\App\Http\Controllers\User\Trading\ExecutionLogController::class, 'closePosition'])->name('position.close');
    });

    // Trading Configuration (unified page with tabs)
    Route::prefix('configuration')->name('configuration.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\Trading\TradingConfigurationController::class, 'index'])->name('index');
    });

    // Backtesting Center (complete CRUD + execution)
    Route::prefix('backtesting')->name('backtesting.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\Trading\BacktestingController::class, 'index'])->name('index');
        Route::post('/', [\App\Http\Controllers\User\Trading\BacktestingController::class, 'store'])->name('store');
        Route::get('/{id}', [\App\Http\Controllers\User\Trading\BacktestingController::class, 'show'])->name('show');
        Route::get('/{id}/export', [\App\Http\Controllers\User\Trading\BacktestingController::class, 'export'])->name('export');
    });

    // Marketplaces (unified marketplace)
    Route::prefix('marketplaces')->name('marketplaces.')->group(function () {
        Route::get('/', [\App\Http\Controllers\User\Trading\MarketplacesController::class, 'index'])->name('index');
    });
});
