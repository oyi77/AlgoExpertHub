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
        // Config dashboard with tabs - loads actual content
        Route::get('/', function () {
            $title = 'Trading Configuration';
            
            // Load all data for tabs
            $connections = \Addons\TradingManagement\Modules\DataProvider\Models\DataConnection::with('admin', 'user')
                ->orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'conn_page');
            
            $presets = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'preset_page');
            
            $smartRiskSettings = \Illuminate\Support\Facades\Cache::get('smart_risk_settings', [
                'enabled' => false,
                'min_provider_score' => 70,
                'slippage_buffer_enabled' => false,
                'dynamic_lot_enabled' => false,
                'max_risk_multiplier' => 2.0,
                'min_risk_multiplier' => 0.5,
            ]);
            
            $stats = [
                'total_connections' => $connections->total(),
                'active_connections' => \Addons\TradingManagement\Modules\DataProvider\Models\DataConnection::where('status', 'active')->count(),
                'total_presets' => $presets->total(),
            ];
            
            return view('trading-management::backend.trading-management.config.index', compact('title', 'stats', 'connections', 'presets', 'smartRiskSettings'));
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

    // 2. Trading Operations (Page with tabs)
    Route::prefix('operations')->name('operations.')->group(function () {
        // Operations dashboard with tabs
        Route::get('/', function () {
            $title = 'Trading Operations';
            $stats = [
                'active_connections' => \Addons\TradingManagement\Modules\Execution\Models\ExecutionConnection::where('is_active', 1)->count(),
                'open_positions' => \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::where('status', 'open')->count(),
                'today_executions' => \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::whereDate('created_at', today())->count(),
                'today_pnl' => \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::where('status', 'closed')->whereDate('closed_at', today())->sum('pnl'),
            ];
            return view('trading-management::backend.trading-management.operations.index', compact('title', 'stats'));
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

    // 3. Trading Strategy (Page with tabs)
    Route::prefix('strategy')->name('strategy.')->group(function () {
        // Strategy dashboard with tabs - loads actual content
        Route::get('/', function () {
            $title = 'Strategy Management';
            
            $filterStrategies = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::with('owner')
                ->orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'filter_page');
            
            $aiProfiles = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::with('owner')
                ->orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'ai_page');
            
            return view('trading-management::backend.trading-management.strategy.index', compact('title', 'filterStrategies', 'aiProfiles'));
        })->name('index');
        
        // Filter Strategies tab
        Route::resource('filters', \Addons\TradingManagement\Modules\FilterStrategy\Controllers\Backend\FilterStrategyController::class);
        
        // AI Model Profiles tab
        Route::resource('ai-models', \Addons\TradingManagement\Modules\AiAnalysis\Controllers\Backend\AiModelProfileController::class);
    });

    // 4. Copy Trading (Page with tabs)
    Route::prefix('copy-trading')->name('copy-trading.')->group(function () {
        // Copy Trading dashboard with tabs - loads actual content
        Route::get('/', function () {
            $title = 'Copy Trading';
            
            $traders = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::select('trader_id')
                ->selectRaw('COUNT(DISTINCT follower_id) as follower_count')
                ->with('trader')
                ->groupBy('trader_id')
                ->orderBy('follower_count', 'desc')
                ->paginate(20, ['*'], 'trader_page');
            
            $subscriptions = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::with(['trader', 'follower'])
                ->orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'sub_page');
            
            $stats = [
                'total_subscriptions' => \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::count(),
                'active_subscriptions' => \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::where('is_active', true)->count(),
                'total_traders' => \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::distinct('trader_id')->count('trader_id'),
            ];
            
            return view('trading-management::backend.trading-management.copy-trading.index', compact('title', 'stats', 'traders', 'subscriptions'));
        })->name('index');
        
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

    // 5. Trading Test (Page with tabs)
    Route::prefix('test')->name('test.')->group(function () {
        // Backtesting dashboard with tabs - loads actual content
        Route::get('/', function () {
            $title = 'Trading Test & Backtesting';
            
            $backtests = \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::with(['admin', 'filterStrategy', 'aiModelProfile', 'preset'])
                ->orderBy('created_at', 'desc')
                ->paginate(20, ['*'], 'bt_page');
            
            $results = \Addons\TradingManagement\Modules\Backtesting\Models\BacktestResult::with('backtest')
                ->orderBy('entry_time', 'desc')
                ->paginate(50, ['*'], 'result_page');
            
            $stats = [
                'total_backtests' => \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::count(),
                'completed' => \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::where('status', 'completed')->count(),
                'running' => \Addons\TradingManagement\Modules\Backtesting\Models\Backtest::where('status', 'running')->count(),
            ];
            
            return view('trading-management::backend.trading-management.test.index', compact('title', 'stats', 'backtests', 'results'));
        })->name('index');
        
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

