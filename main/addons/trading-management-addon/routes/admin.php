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

    // 1. Trading Configuration (Page with tabs)
    Route::prefix('config')->name('config.')->group(function () {
        // Config dashboard with tabs
        Route::get('/', function () {
            $title = 'Trading Configuration';
            $stats = [
                'total_connections' => \Addons\TradingManagement\Modules\DataProvider\Models\DataConnection::count(),
                'active_connections' => \Addons\TradingManagement\Modules\DataProvider\Models\DataConnection::where('status', 'active')->count(),
                'total_presets' => \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::count(),
            ];
            return view('trading-management::backend.trading-management.config.index', compact('title', 'stats'));
        })->name('index');
        
        // Data Connections
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
        
        // Risk Presets
        Route::resource('risk-presets', \Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend\RiskPresetController::class);
        
        // Smart Risk Settings
        Route::get('smart-risk', [\Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend\SmartRiskController::class, 'index'])
            ->name('smart-risk.index');
        Route::post('smart-risk', [\Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend\SmartRiskController::class, 'update'])
            ->name('smart-risk.update');
    });

    // 2. Trading Operations (Submenu with tabs)
    Route::prefix('operations')->name('operations.')->group(function () {
        // Operations dashboard
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.operations.index');
        })->name('index');
        
        // Execution Connections tab
        Route::resource('connections', \Addons\TradingManagement\Modules\Execution\Controllers\Backend\ExecutionConnectionController::class);
        Route::post('connections/test', [\Addons\TradingManagement\Modules\Execution\Controllers\Backend\ExecutionConnectionController::class, 'test'])
            ->name('connections.test');
        Route::post('connections/{connection}/activate', [\Addons\TradingManagement\Modules\Execution\Controllers\Backend\ExecutionConnectionController::class, 'activate'])
            ->name('connections.activate');
        Route::post('connections/{connection}/deactivate', [\Addons\TradingManagement\Modules\Execution\Controllers\Backend\ExecutionConnectionController::class, 'deactivate'])
            ->name('connections.deactivate');
        
        // Executions Log tab
        Route::get('executions', [\Addons\TradingManagement\Modules\Execution\Controllers\Backend\TradingOperationsController::class, 'executions'])->name('executions');
        
        // Positions tabs
        Route::get('positions/open', [\Addons\TradingManagement\Modules\Execution\Controllers\Backend\TradingOperationsController::class, 'openPositions'])->name('positions.open');
        Route::get('positions/closed', [\Addons\TradingManagement\Modules\Execution\Controllers\Backend\TradingOperationsController::class, 'closedPositions'])->name('positions.closed');
        
        // Analytics tab
        Route::get('analytics', [\Addons\TradingManagement\Modules\Execution\Controllers\Backend\TradingOperationsController::class, 'analytics'])->name('analytics');
    });

    // 3. Trading Strategy (Submenu with tabs)
    Route::prefix('strategy')->name('strategy.')->group(function () {
        // Strategy dashboard with tabs
        Route::get('/', function () {
            return view('trading-management::backend.trading-management.strategy.index');
        })->name('index');
        
        // Filter Strategies tab
        Route::resource('filters', \Addons\TradingManagement\Modules\FilterStrategy\Controllers\Backend\FilterStrategyController::class);
        
        // AI Model Profiles tab
        Route::resource('ai-models', \Addons\TradingManagement\Modules\AiAnalysis\Controllers\Backend\AiModelProfileController::class);
    });

    // 4. Copy Trading (Submenu with tabs)
    Route::prefix('copy-trading')->name('copy-trading.')->group(function () {
        // Copy Trading dashboard
        Route::get('/', [\Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend\CopyTradingController::class, 'index'])->name('index');
        
        // Subscriptions
        Route::get('subscriptions', [\Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend\CopyTradingController::class, 'subscriptions'])->name('subscriptions');
        Route::post('subscriptions/{subscription}/toggle', [\Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend\CopyTradingController::class, 'toggleSubscription'])->name('subscriptions.toggle');
        Route::delete('subscriptions/{subscription}', [\Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend\CopyTradingController::class, 'destroySubscription'])->name('subscriptions.destroy');
        
        // Traders & Followers
        Route::get('traders', [\Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend\CopyTradingController::class, 'traders'])->name('traders');
        Route::get('followers', [\Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend\CopyTradingController::class, 'followers'])->name('followers');
        
        // Executions & Analytics
        Route::get('executions', [\Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend\CopyTradingController::class, 'executions'])->name('executions');
        Route::get('analytics', [\Addons\TradingManagement\Modules\CopyTrading\Controllers\Backend\CopyTradingController::class, 'analytics'])->name('analytics');
    });

    // 5. Trading Test (Submenu with tabs)
    Route::prefix('test')->name('test.')->group(function () {
        // Backtesting dashboard
        Route::get('/', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'index'])->name('index');
        
        // Backtest operations
        Route::get('backtests', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'backtests'])->name('backtests.index');
        Route::get('backtests/create', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'create'])->name('backtests.create');
        Route::post('backtests', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'store'])->name('backtests.store');
        Route::get('backtests/{backtest}', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'show'])->name('backtests.show');
        Route::delete('backtests/{backtest}', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'destroy'])->name('backtests.destroy');
        
        // Results
        Route::get('results', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'results'])->name('results.index');
    });

// /config is now handled by the config.index route above

// Removed - now in operations prefix group above

// Removed - now handled in respective prefix groups above

