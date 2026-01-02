<?php

use App\Http\Controllers\CryptoTradeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SignalController;

/*
|--------------------------------------------------------------------------
| Core Trading Routes
|--------------------------------------------------------------------------
| Signals, plans, legacy trade, and trading terminal.
*/

        // Signals
        Route::get('all-signals', [SignalController::class, 'allSignals'])->name('signal.all');
        Route::get('signal-details/{id}/{slug}', [SignalController::class, 'details'])->name('signal.details');

        // Plans
        Route::get('plans', [PlanController::class, 'plans'])->name('plans');
        Route::post('plans', [PlanController::class, 'subscribe'])->name('plans.post');

        // Legacy trade (demo/practice mode)
        Route::get('trade', [CryptoTradeController::class, 'index'])->name('trade');
        Route::post('trade', [CryptoTradeController::class, 'openTrade']);
        Route::get('trades', [CryptoTradeController::class, 'trades'])->name('trades');
        Route::get('trade-close', [CryptoTradeController::class, 'tradeClose'])->name('tradeClose');

        // Trading Terminal (professional terminal with real-time data)
        Route::prefix('terminal')->name('terminal.')->group(function () {
            Route::get('/', [\App\Http\Controllers\TradingTerminalController::class, 'index'])->name('index');
            Route::post('/order', [\App\Http\Controllers\TradingTerminalController::class, 'placeOrder'])->name('order.place');
            Route::delete('/position/{id}', [\App\Http\Controllers\TradingTerminalController::class, 'closePosition'])->name('position.close');
            Route::get('/positions', [\App\Http\Controllers\TradingTerminalController::class, 'getPositions'])->name('positions');
            Route::get('/market-data', [\App\Http\Controllers\TradingTerminalController::class, 'getMarketData'])->name('market-data');
        });
