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

// Unified Exchange Connections (replaces separate data + execution connections)
Route::prefix('exchange-connections')->name('exchange-connections.')->group(function () {
    Route::get('/', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'index'])->name('index');
    Route::get('/create', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'create'])->name('create');
    Route::post('/', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'store'])->name('store');
    Route::get('/{exchangeConnection}', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'show'])->name('show');
    Route::get('/{exchangeConnection}/edit', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'edit'])->name('edit');
    Route::put('/{exchangeConnection}', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'update'])->name('update');
    Route::delete('/{exchangeConnection}', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'destroy'])->name('destroy');
    
    // Testing endpoints
    Route::post('/test-data-fetch', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'testDataFetch'])->name('test-data-fetch');
    Route::post('/test-execution', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'testExecution'])->name('test-execution');
});

    // 1. Trading Configuration (Page with tabs)
    Route::prefix('config')->name('config.')->group(function () {
        // Config dashboard with tabs - loads actual content
        Route::get('/', function () {
            $title = 'Trading Configuration';
            
            try {
                // Load Exchange Connections (unified)
                $connections = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::with('admin', 'user', 'preset')
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
                    'data_connections' => \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('is_active', 1)->count(),
                    'execution_connections' => \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('is_active', 1)->count(),
                    'total_presets' => $presets->total(),
                ];
            } catch (\Exception $e) {
                \Log::error('Trading config error', ['error' => $e->getMessage()]);
                $connections = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                $presets = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                $smartRiskSettings = ['enabled' => false];
                $stats = ['total_connections' => 0, 'data_connections' => 0, 'execution_connections' => 0, 'total_presets' => 0, 'error' => $e->getMessage()];
            }
            
            return view('trading-management::backend.trading-management.config.index', compact('title', 'stats', 'connections', 'presets', 'smartRiskSettings'));
        })->name('index');
        
        // Exchange Connections (unified - replaces data-connections)
        Route::prefix('exchange-connections')->name('exchange-connections.')->group(function () {
            Route::get('/', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'index'])->name('index');
            Route::get('/create', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'create'])->name('create');
            Route::post('/', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'store'])->name('store');
            Route::get('/{exchangeConnection}', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'show'])->name('show');
            Route::get('/{exchangeConnection}/edit', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'edit'])->name('edit');
            Route::put('/{exchangeConnection}', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'update'])->name('update');
            Route::delete('/{exchangeConnection}', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'destroy'])->name('destroy');
            
            // Testing endpoints
            Route::post('/test-data-fetch', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'testDataFetch'])->name('test-data-fetch');
            Route::post('/test-execution', [\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class, 'testExecution'])->name('test-execution');
        });
        
        // Risk Presets
        Route::resource('risk-presets', \Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend\RiskPresetController::class);
        
        // Smart Risk Settings
        Route::get('smart-risk', [\Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend\SmartRiskController::class, 'index'])
            ->name('smart-risk.index');
        Route::post('smart-risk', [\Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend\SmartRiskController::class, 'update'])
            ->name('smart-risk.update');
        
        // Global Settings
        Route::get('global-settings', [\Addons\TradingManagement\Modules\GlobalSettings\Controllers\Backend\GlobalSettingsController::class, 'index'])
            ->name('global-settings.index');
        Route::post('global-settings', [\Addons\TradingManagement\Modules\GlobalSettings\Controllers\Backend\GlobalSettingsController::class, 'update'])
            ->name('global-settings.update');
        Route::post('global-settings/test-demo', [\Addons\TradingManagement\Modules\GlobalSettings\Controllers\Backend\GlobalSettingsController::class, 'testDemoConnection'])
            ->name('global-settings.test-demo');
    });

    // 2. Trading Operations (Page with tabs)
    Route::prefix('operations')->name('operations.')->group(function () {
        // Operations dashboard with tabs
        Route::get('/', function () {
            $title = 'Trading Operations';
            
            try {
                // Use old addon models until migration complete
                $ExecutionPosition = class_exists(\Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class)
                    ? \Addons\TradingManagement\Modules\PositionMonitoring\Models\ExecutionPosition::class
                    : \Addons\TradingExecutionEngine\App\Models\ExecutionPosition::class;
                
                $ExecutionLog = class_exists(\Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class)
                    ? \Addons\TradingManagement\Modules\Execution\Models\ExecutionLog::class
                    : \Addons\TradingExecutionEngine\App\Models\ExecutionLog::class;
                
                $ExecutionConnection = class_exists(\Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class)
                    ? \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class
                    : \Addons\TradingExecutionEngine\App\Models\ExecutionConnection::class;
                
                $stats = [
                    'active_connections' => $ExecutionConnection::where('is_active', 1)->count(),
                    'open_positions' => $ExecutionPosition::where('status', 'open')->count(),
                    'today_executions' => $ExecutionLog::whereDate('created_at', today())->count(),
                    'today_pnl' => $ExecutionPosition::where('status', 'closed')->whereDate('closed_at', today())->sum('pnl') ?? 0,
                ];
            } catch (\Exception $e) {
                \Log::error('Trading operations stats error', ['error' => $e->getMessage()]);
                $stats = [
                    'active_connections' => 0,
                    'open_positions' => 0,
                    'today_executions' => 0,
                    'today_pnl' => 0,
                    'error' => $e->getMessage(),
                ];
            }
            
            return view('trading-management::backend.trading-management.operations.index', compact('title', 'stats'));
        })->name('index');
        
        // Manual trade execution
        Route::post('manual-trade', [\Addons\TradingManagement\Modules\Execution\Controllers\Backend\TradingOperationsController::class, 'manualTrade'])->name('manual-trade');
        
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
            
            try {
                $filterStrategies = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::with('owner')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20, ['*'], 'filter_page');
                
                $aiProfiles = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::with('owner')
                    ->orderBy('created_at', 'desc')
                    ->paginate(20, ['*'], 'ai_page');
            } catch (\Exception $e) {
                \Log::error('Trading strategy error', ['error' => $e->getMessage()]);
                $filterStrategies = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                $aiProfiles = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
            }
            
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
            
            try {
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
            } catch (\Exception $e) {
                \Log::error('Copy trading dashboard error', ['error' => $e->getMessage()]);
                $traders = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                $subscriptions = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                $stats = ['total_subscriptions' => 0, 'active_subscriptions' => 0, 'total_traders' => 0, 'error' => $e->getMessage()];
            }
            
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
            
            try {
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
            } catch (\Exception $e) {
                \Log::error('Trading test dashboard error', ['error' => $e->getMessage()]);
                $backtests = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                $results = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 50, 1);
                $stats = ['total_backtests' => 0, 'completed' => 0, 'running' => 0, 'error' => $e->getMessage()];
            }
            
            return view('trading-management::backend.trading-management.test.index', compact('title', 'stats', 'backtests', 'results'));
        })->name('index');
        
        // Data Download for ML/AI/Backtesting
        Route::post('download-data', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'downloadData'])->name('download-data');
        
        // Backtest operations
        Route::get('backtests', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'backtests'])->name('backtests.index');
        Route::get('backtests/create', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'create'])->name('backtests.create');
        Route::post('backtests', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'store'])->name('backtests.store');
        Route::get('backtests/{backtest}', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'show'])->name('backtests.show');
        Route::delete('backtests/{backtest}', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'destroy'])->name('backtests.destroy');
        
        // Data availability checks (AJAX)
        Route::post('backtests/check-data', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'checkDataAvailability'])->name('backtests.check-data');
        Route::post('backtests/validate-dates', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'validateDateRange'])->name('backtests.validate-dates');
        
        // Results
        Route::get('results', [\Addons\TradingManagement\Modules\Backtesting\Controllers\Backend\BacktestController::class, 'results'])->name('results.index');
    });

    // 6. Trading Bots (Coinrule-like bot builder)
    Route::prefix('trading-bots')->name('trading-bots.')->group(function () {
        Route::get('/', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'index'])->name('index');
        Route::get('/create', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'create'])->name('create');
        Route::post('/', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'store'])->name('store');
        Route::get('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'show'])->name('show');
        Route::get('/{id}/edit', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'edit'])->name('edit');
        Route::put('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'update'])->name('update');
        Route::delete('/{id}', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'destroy'])->name('destroy');
        Route::post('/{id}/toggle-active', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'toggleActive'])->name('toggle-active');
        Route::post('/{id}/start', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'start'])->name('start');
        Route::post('/{id}/stop', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'stop'])->name('stop');
        Route::post('/{id}/pause', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'pause'])->name('pause');
        Route::post('/{id}/resume', [\Addons\TradingManagement\Modules\TradingBot\Controllers\Backend\TradingBotController::class, 'resume'])->name('resume');
    });

    // 7. Marketplace (Bot Templates & Trader Profiles)
    Route::prefix('marketplace')->name('marketplace.')->group(function () {
        // Bot Templates
        Route::get('bots', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\BotMarketplaceController::class, 'index'])->name('bots.index');
        Route::get('bots/{id}', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\BotMarketplaceController::class, 'show'])->name('bots.show');
        Route::post('bots/{id}/approve', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\BotMarketplaceController::class, 'approve'])->name('bots.approve');
        Route::post('bots/{id}/feature', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\BotMarketplaceController::class, 'feature'])->name('bots.feature');
        Route::delete('bots/{id}', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\BotMarketplaceController::class, 'destroy'])->name('bots.destroy');
        
        // Trader Profiles
        Route::get('traders', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\TraderMarketplaceController::class, 'index'])->name('traders.index');
        Route::get('traders/{id}', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\TraderMarketplaceController::class, 'show'])->name('traders.show');
        Route::post('traders/{id}/verify', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\TraderMarketplaceController::class, 'verify'])->name('traders.verify');
        Route::delete('traders/{id}', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\TraderMarketplaceController::class, 'destroy'])->name('traders.destroy');
        Route::post('traders/recalculate-leaderboard', [\Addons\TradingManagement\Modules\Marketplace\Controllers\Backend\TraderMarketplaceController::class, 'recalculateLeaderboard'])->name('traders.recalculate');
    });

