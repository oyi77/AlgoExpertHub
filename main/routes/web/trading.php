<?php

use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Trading Routes
|--------------------------------------------------------------------------
|
| All trading-related routes including unified trading pages, trading
| management addon routes, signals, plans, legacy trade, and terminal.
|
| This file now includes modular route files for better organization.
|
*/

Route::name('user.')->middleware(['auth', 'inactive', 'is_email_verified', '2fa', 'kyc'])->group(function () {
    Route::middleware('check_onboarding')->group(function () {
        Route::get('dashboard', [UserController::class, 'dashboard'])->name('dashboard');

        // Unified Trading Pages
        require __DIR__ . '/trading-unified.php';

            // Beta Routes (New React UI)
            Route::prefix('beta')->name('beta.')->group(function () {
                Route::get('/dashboard', [UserController::class, 'betaDashboard'])->name('dashboard');

                // Signal Center in Beta
                Route::get('/signals', [\App\Http\Controllers\SignalController::class, 'betaIndex'])->name('signals.index');
                Route::get('/signals/{id}', [\App\Http\Controllers\SignalController::class, 'betaDetails'])->name('signals.details');

                // Help Center in Beta
                Route::prefix('help')->name('help.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\User\HelpController::class, 'betaIndex'])->name('index');
                    Route::get('/topic/{topic}', [\App\Http\Controllers\User\HelpController::class, 'betaTopic'])->name('topic');
                });

                // Unified Trading Pages in Beta
                Route::prefix('trading')->name('trading.')->group(function () {
                    // Multi-Channel Signal
                    Route::prefix('multi-channel-signal')->name('multi-channel-signal.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\User\Trading\MultiChannelSignalController::class, 'betaIndex'])->name('index');
                    });

                    // Trading Operations
                    Route::prefix('operations')->name('operations.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\User\Trading\TradingOperationsController::class, 'betaIndex'])->name('index');
                    });

                    // Execution Log
                    Route::prefix('execution-log')->name('execution-log.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\User\Trading\ExecutionLogController::class, 'betaIndex'])->name('index');
                    });

                    // Trading Configuration
                    Route::prefix('configuration')->name('configurations.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\User\Trading\TradingConfigurationController::class, 'betaIndex'])->name('index');
                    });

                    // Backtesting
                    Route::prefix('backtesting')->name('backtesting.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\User\Trading\BacktestingController::class, 'betaIndex'])->name('index');
                    });

                    // Marketplaces
                    Route::prefix('marketplaces')->name('marketplaces.')->group(function () {
                        Route::get('/', [\App\Http\Controllers\User\Trading\MarketplacesController::class, 'betaIndex'])->name('index');
                    });
                });

                // Terminal in Beta
                Route::prefix('terminal')->name('terminal.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\TradingTerminalController::class, 'betaIndex'])->name('index');
                    Route::post('/order', [\App\Http\Controllers\TradingTerminalController::class, 'placeOrder'])->name('order.place');
                    Route::delete('/position/{id}', [\App\Http\Controllers\TradingTerminalController::class, 'closePosition'])->name('position.close');
                    Route::get('/positions', [\App\Http\Controllers\TradingTerminalController::class, 'getPositions'])->name('positions');
                    Route::get('/market-data', [\App\Http\Controllers\TradingTerminalController::class, 'getMarketData'])->name('market-data');
                });
            });

        // Help Center
        Route::prefix('help')->name('help.')->group(function () {
            Route::get('/', [\App\Http\Controllers\User\HelpController::class, 'index'])->name('index');
            Route::get('/topic/{topic}', [\App\Http\Controllers\User\HelpController::class, 'topic'])->name('topic');
        });

        // Backward Compatibility Redirects
        require __DIR__ . '/trading-redirects.php';

        // Trading Management Addon Routes
        if (\App\Support\AddonRegistry::active('trading-management-addon')) {
            require __DIR__ . '/trading-management-presets.php';
            require __DIR__ . '/trading-management-copy.php';
            require __DIR__ . '/trading-management-connections.php';
        }

        // Core Trading Routes (Signals, Plans, Legacy Trade, Terminal)
        require __DIR__ . '/trading-core.php';
    });
});
