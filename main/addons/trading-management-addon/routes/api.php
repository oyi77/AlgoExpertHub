<?php

use Illuminate\Support\Facades\Route;
use Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Api\ExchangeConnectionStatusController;
use Addons\TradingManagement\Modules\PositionMonitoring\Controllers\Api\PositionController;

/*
|--------------------------------------------------------------------------
| Trading Management API Routes
|--------------------------------------------------------------------------
|
| API routes for real-time connection status and control
|
*/

Route::prefix('exchange-connections')->name('exchange-connections.')->group(function () {
    Route::get('/{connection}/status', [ExchangeConnectionStatusController::class, 'status'])->name('status');
    Route::post('/{connection}/test', [ExchangeConnectionStatusController::class, 'test'])->name('test');
    Route::get('/{connection}/stabilized', [ExchangeConnectionStatusController::class, 'stabilized'])->name('stabilized');
});

Route::prefix('trading-bots')->name('trading-bots.')->group(function () {
    Route::get('/{bot}/positions', [PositionController::class, 'index'])->name('positions.index');
    Route::patch('/{bot}/positions/{position}', [PositionController::class, 'update'])->name('positions.update');
    Route::post('/{bot}/positions/{position}/close', [PositionController::class, 'close'])->name('positions.close');
    Route::get('/{bot}/balance', [PositionController::class, 'balance'])->name('balance');
});
