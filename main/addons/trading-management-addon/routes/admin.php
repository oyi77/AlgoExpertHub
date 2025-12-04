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
        // Data Connections tab (Phase 2) - ACTIVE
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
        
        // Risk Presets tab (Phase 4)
        // Route::resource('risk-presets', Backend\RiskPresetController::class);
        
        // Smart Risk Settings tab (Phase 4 - admin only)
        // Route::resource('smart-risk', Backend\SmartRiskController::class);
    });

    // 2. Trading Operations (Submenu with tabs)
    Route::prefix('operations')->name('operations.')->group(function () {
        // Operations dashboard with tabs (Phase 7 - ACTIVE)
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.operations.index');
        })->name('index');
        
        // Execution Connections tab (Phase 5 - structure ready, UI Phase 7+)
        // Route::resource('connections', Backend\ExecutionConnectionController::class);
        
        // Executions Log tab (Phase 7+)
        // Route::get('executions', [Backend\TradingOperationsController::class, 'executions'])->name('executions');
        
        // Positions tabs (Phase 7+)
        // Route::get('positions/open', [Backend\TradingOperationsController::class, 'openPositions'])->name('positions.open');
        // Route::get('positions/closed', [Backend\TradingOperationsController::class, 'closedPositions'])->name('positions.closed');
        
        // Analytics tab (Phase 7+)
        // Route::get('analytics', [Backend\TradingOperationsController::class, 'analytics'])->name('analytics');
    });

    // 3. Trading Strategy (Submenu with tabs)
    Route::prefix('strategy')->name('strategy.')->group(function () {
        // Strategy dashboard with tabs (Phase 7 - ACTIVE)
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.strategy.index');
        })->name('index');
        
        // Filter Strategies tab (Phase 3 - models ready, UI Phase 7+)
        // Route::resource('filters', Backend\FilterStrategyController::class);
        
        // AI Model Profiles tab (Phase 3 - models ready, UI Phase 7+)
        // Route::resource('ai-models', Backend\AiModelProfileController::class);
        
        // Decision Logs tab (Phase 7+)
        // Route::get('decision-logs', [Backend\AiDecisionLogController::class, 'index'])->name('decision-logs');
    });

    // 4. Copy Trading (Submenu with tabs)
    Route::prefix('copy-trading')->name('copy-trading.')->group(function () {
        // Copy Trading dashboard with tabs (Phase 7 - ACTIVE)
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.copy-trading.index');
        })->name('index');
        
        // Traders, subscriptions, analytics (Phase 7+)
        // Route::resource('subscriptions', Backend\CopyTradingSubscriptionController::class);
    });

    // 5. Trading Test (Submenu with tabs)
    Route::prefix('test')->name('test.')->group(function () {
        // Backtesting dashboard with tabs (Phase 7 - ACTIVE)
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.test.index');
        })->name('index');
        
        // Backtest operations (Phase 8)
        // Route::resource('backtests', Backend\BacktestController::class);
    });

// Redirect /config to data-connections
Route::get('/config', function () {
    return redirect()->route('admin.trading-management.config.data-connections.index');
});

// Removed - now in operations prefix group above

// Removed - now handled in respective prefix groups above

