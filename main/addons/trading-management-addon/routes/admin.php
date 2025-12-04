<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Trading Management - Admin Routes
|--------------------------------------------------------------------------
|
| All trading management routes for admin panel
| 
| Structure:
| - /admin/trading-management (dashboard)
| - /admin/trading-management/config/* (Trading Configuration submenu)
| - /admin/trading-management/operations/* (Trading Operations submenu)
| - /admin/trading-management/strategy/* (Trading Strategy submenu)
| - /admin/trading-management/copy-trading/* (Copy Trading submenu)
| - /admin/trading-management/test/* (Trading Test submenu)
|
*/

// Dashboard (overview)
Route::get('/', function () {
    return view('trading-management::backend.dashboard');
})->name('dashboard');

    // 1. Trading Configuration (Submenu)
    Route::prefix('config')->name('config.')->group(function () {
        // Data Connections tab - FULLY IMPLEMENTED
        Route::resource('data-connections', \Addons\TradingManagement\Modules\DataProvider\Controllers\Backend\DataConnectionController::class);
        Route::post('data-connections/test', [\Addons\TradingManagement\Modules\DataProvider\Controllers\Backend\DataConnectionController::class, 'test'])
            ->name('data-connections.test');
        Route::post('data-connections/{dataConnection}/activate', [\Addons\TradingManagement\Modules\DataProvider\Controllers\Backend\DataConnectionController::class, 'activate'])
            ->name('data-connections.activate');
        Route::post('data-connections/{dataConnection}/deactivate', [\Addons\TradingManagement\Modules\DataProvider\Controllers\Backend\DataConnectionController::class, 'deactivate'])
            ->name('data-connections.deactivate');
        Route::get('data-connections/{dataConnection}/market-data', [\Addons\TradingManagement\Modules\DataProvider\Controllers\Backend\DataConnectionController::class, 'marketData'])
            ->name('data-connections.market-data');
        Route::get('data-connections/{dataConnection}/logs', [\Addons\TradingManagement\Modules\DataProvider\Controllers\Backend\DataConnectionController::class, 'logs'])
            ->name('data-connections.logs');
        
        // Risk Presets tab - NOW ACTIVE
        Route::resource('risk-presets', \Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend\TradingPresetController::class);
    });

    // 2. Trading Operations (Submenu with tabs)
    Route::prefix('operations')->name('operations.')->group(function () {
        // Operations dashboard with tabs
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.operations.index');
        })->name('index');
        
        // Connections tab - ACTIVE
        Route::resource('connections', \Addons\TradingManagement\Modules\Execution\Controllers\Backend\ExecutionConnectionController::class);
        
        // Executions Log tab
        Route::get('executions', function () {
            return view('trading-management::backend.trading-management.operations.executions.index');
        })->name('executions.index');
        
        // Positions tabs
        Route::get('positions/open', function () {
            return view('trading-management::backend.trading-management.operations.positions.open');
        })->name('positions.open');
        
        Route::get('positions/closed', function () {
            return view('trading-management::backend.trading-management.operations.positions.closed');
        })->name('positions.closed');
        
        // Analytics tab
        Route::get('analytics', function () {
            return view('trading-management::backend.trading-management.operations.analytics.index');
        })->name('analytics.index');
    });

    // 3. Trading Strategy (Submenu with tabs)
    Route::prefix('strategy')->name('strategy.')->group(function () {
        // Strategy dashboard with tabs
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.strategy.index');
        })->name('index');
        
        // Filter Strategies tab - ACTIVE
        Route::resource('filters', \Addons\TradingManagement\Modules\FilterStrategy\Controllers\Backend\FilterStrategyController::class);
        
        // AI Model Profiles tab - ACTIVE
        Route::resource('ai-models', \Addons\TradingManagement\Modules\AiAnalysis\Controllers\Backend\AiModelProfileController::class);
    });

    // 4. Copy Trading (Submenu with tabs)
    Route::prefix('copy-trading')->name('copy-trading.')->group(function () {
        // Copy Trading dashboard with tabs
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.copy-trading.index');
        })->name('index');
        
        // Traders tab
        Route::get('traders', function () {
            return view('trading-management::backend.trading-management.copy-trading.traders.index');
        })->name('traders.index');
        
        // Subscriptions tab
        Route::get('subscriptions', function () {
            return view('trading-management::backend.trading-management.copy-trading.subscriptions.index');
        })->name('subscriptions.index');
    });

    // 5. Trading Test (Submenu with tabs)
    Route::prefix('test')->name('test.')->group(function () {
        // Backtesting dashboard with tabs
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.test.index');
        })->name('index');
        
        // Backtesting tabs
        Route::get('backtests/create', function () {
            return view('trading-management::backend.trading-management.test.backtests.create');
        })->name('backtests.create');
        
        Route::get('backtests', function () {
            return view('trading-management::backend.trading-management.test.backtests.index');
        })->name('backtests.index');
    });

// Redirect /config to data-connections
Route::get('/config', function () {
    return redirect()->route('admin.trading-management.config.data-connections.index');
});

// Removed - now in operations prefix group above

// Removed - now handled in respective prefix groups above

