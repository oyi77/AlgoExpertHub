<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Helpers\NotificationHelper;

/*
|--------------------------------------------------------------------------
| Trading Management - Copy Trading & Smart Risk Management
|--------------------------------------------------------------------------
*/

        if (\App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'copy_trading')) {
            Route::prefix('copy-trading')->name('copy-trading.')->group(function () {
                Route::get('/settings', function () {
                    try {
                        $title = 'Copy Trading Settings';
                        
                        if (!\Schema::hasTable('copy_trading_settings')) {
                            \Log::warning('Copy trading settings table does not exist');
                            return view('trading-management::user.copy-trading.settings', [
                                'title' => $title,
                                'error' => 'Copy trading settings table does not exist. Please run migrations.'
                            ]);
                        }
                        
                        $setting = null;
                        try {
                            $deprecatedModel = \Addons\CopyTrading\App\Models\CopyTradingSetting::class;
                            if (class_exists($deprecatedModel)) {
                                $setting = $deprecatedModel::firstOrCreate(
                                    ['user_id' => auth()->id()],
                                    [
                                        'is_enabled' => false,
                                        'risk_multiplier_default' => 1.0,
                                        'allow_manual_trades' => true,
                                        'allow_auto_trades' => true,
                                    ]
                                );
                            }
                        } catch (\Exception $e) {
                            \Log::error('Error loading copy trading settings: ' . $e->getMessage());
                        }
                        
                        $stats = [
                            'follower_count' => 0,
                            'total_copied_trades' => 0,
                        ];
                        
                        try {
                            $subscriptionModel = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
                            if (class_exists($subscriptionModel)) {
                                $stats['follower_count'] = $subscriptionModel::where('trader_id', auth()->id())
                                    ->where('is_active', true)
                                    ->count();
                            } elseif (class_exists(\Addons\CopyTrading\App\Models\CopyTradingSubscription::class)) {
                                $stats['follower_count'] = \Addons\CopyTrading\App\Models\CopyTradingSubscription::where('trader_id', auth()->id())
                                    ->where('is_active', true)
                                    ->count();
                            }
                        } catch (\Exception $e) {
                            // Stats not critical, continue
                        }
                        
                        return view('trading-management::user.copy-trading.settings', compact('title', 'setting', 'stats'));
                    } catch (\Exception $e) {
                        \Log::error('Copy trading settings error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        return view('trading-management::user.copy-trading.settings', [
                            'title' => 'Copy Trading Settings',
                            'error' => 'An error occurred while loading settings. Please check the logs.'
                        ]);
                    }
                })->name('settings');
                
                Route::get('/traders', function () {
                    try {
                        $title = 'Browse Traders';
                        
                        if (!\Schema::hasTable('trader_profiles')) {
                            \Log::warning('Trader profiles table does not exist');
                            return view('trading-management::user.copy-trading.traders.index', [
                                'traders' => collect([])->paginate(20),
                                'title' => $title
                            ]);
                        }
                        
                        $traders = \Addons\TradingManagement\Modules\Marketplace\Models\TraderProfile::public()
                            ->verified()
                            ->with('user')
                            ->orderBy('total_profit_percent', 'desc')
                            ->paginate(20);
                        return view('trading-management::user.copy-trading.traders.index', compact('traders', 'title'));
                    } catch (\Exception $e) {
                        \Log::error('Copy trading traders error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        return view('trading-management::user.copy-trading.traders.index', [
                            'traders' => collect([])->paginate(20),
                            'title' => 'Browse Traders'
                        ]);
                    }
                })->name('traders.index');
                
                Route::get('/traders/{id}', function ($id) {
                    try {
                        $title = 'Trader Profile';
                        
                        if (!\Schema::hasTable('trader_profiles')) {
                            \Log::warning('Trader profiles table does not exist');
                            abort(404, 'Trader profile not found');
                        }
                        
                        $trader = \Addons\TradingManagement\Modules\Marketplace\Models\TraderProfile::with(['user', 'ratings'])
                            ->where('user_id', $id)
                            ->public()
                            ->firstOrFail();
                        
                        $isFollowing = false;
                        try {
                            $subscriptionModel = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
                            if (class_exists($subscriptionModel)) {
                                $isFollowing = $subscriptionModel::where('trader_id', $id)
                                    ->where('follower_id', auth()->id())
                                    ->where('is_active', true)
                                    ->exists();
                            }
                        } catch (\Exception $e) {
                            // Not critical
                        }
                        
                        return view('trading-management::user.copy-trading.traders.show', compact('trader', 'title', 'isFollowing'));
                    } catch (\Exception $e) {
                        \Log::error('Copy trading trader show error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        abort(404, 'Trader profile not found');
                    }
                })->name('traders.show');
                
                Route::get('/subscriptions', function () {
                    try {
                        $title = 'My Copy Trading Subscriptions';
                        
                        if (!\Schema::hasTable('copy_trading_subscriptions')) {
                            \Log::warning('Copy trading subscriptions table does not exist');
                            return view('trading-management::user.copy-trading.subscriptions.index', [
                                'subscriptions' => collect([])->paginate(20),
                                'title' => $title
                            ]);
                        }
                        
                        $subscriptions = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::where('follower_id', auth()->id())
                            ->with(['trader', 'preset'])
                            ->orderBy('created_at', 'desc')
                            ->paginate(20);
                        return view('trading-management::user.copy-trading.subscriptions.index', compact('subscriptions', 'title'));
                    } catch (\Exception $e) {
                        \Log::error('Copy trading subscriptions error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        return view('trading-management::user.copy-trading.subscriptions.index', [
                            'subscriptions' => collect([])->paginate(20),
                            'title' => 'My Copy Trading Subscriptions'
                        ]);
                    }
                })->name('subscriptions.index');
                
                Route::get('/history', function () {
                    try {
                        $title = 'Copy Trading History';
                        
                        if (!\Schema::hasTable('copy_trading_executions')) {
                            \Log::warning('Copy trading executions table does not exist');
                            return view('trading-management::user.copy-trading.history.index', [
                                'executions' => collect([])->paginate(20),
                                'title' => $title
                            ]);
                        }
                        
                        $executions = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingExecution::where('follower_id', auth()->id())
                            ->with(['subscription', 'trader'])
                            ->orderBy('created_at', 'desc')
                            ->paginate(20);
                        return view('trading-management::user.copy-trading.history.index', compact('executions', 'title'));
                    } catch (\Exception $e) {
                        \Log::error('Copy trading history error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        return view('trading-management::user.copy-trading.history.index', [
                            'executions' => collect([])->paginate(20),
                            'title' => 'Copy Trading History'
                        ]);
                    }
                })->name('history.index');
            });
        }

        // Smart Risk Management
        if (\App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'risk_management')) {
            Route::prefix('srm')->name('srm.')->group(function () {
                Route::get('/', function () {
                    try {
                        $title = 'Smart Risk Management Dashboard';
                        return view('trading-management::user.smart-risk.dashboard', compact('title'));
                    } catch (\Exception $e) {
                        \Log::error('SRM dashboard error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        return view('trading-management::user.smart-risk.dashboard', [
                            'title' => 'Smart Risk Management Dashboard'
                        ]);
                    }
                })->name('dashboard');
                
                Route::get('/adjustments', function () {
                    try {
                        $title = 'SRM Adjustments';
                        return view('trading-management::user.smart-risk.adjustments.index', compact('title'));
                    } catch (\Exception $e) {
                        \Log::error('SRM adjustments error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        return view('trading-management::user.smart-risk.adjustments.index', [
                            'title' => 'SRM Adjustments'
                        ]);
                    }
                })->name('adjustments.index');
                
                Route::get('/insights', function () {
                    try {
                        $title = 'SRM Insights';
                        return view('trading-management::user.smart-risk.insights.index', compact('title'));
                    } catch (\Exception $e) {
                        \Log::error('SRM insights error: ' . $e->getMessage(), [
                            'trace' => $e->getTraceAsString(),
                            'file' => $e->getFile(),
                            'line' => $e->getLine()
                        ]);
                        return view('trading-management::user.smart-risk.insights.index', [
                            'title' => 'SRM Insights'
                        ]);
                    }
                })->name('insights.index');
                
                Route::post('/settings/update', function (\Illuminate\Http\Request $request) {
                    try {
                        $validated = $request->validate([
                            'enabled' => 'nullable|boolean',
                            'min_provider_score' => 'nullable|numeric|min:0|max:100',
                            'slippage_buffer_enabled' => 'nullable|boolean',
                            'dynamic_lot_enabled' => 'nullable|boolean',
                        ]);
                        
                        $settings = \Illuminate\Support\Facades\Cache::get('smart_risk_settings_' . auth()->id(), [
                            'enabled' => false,
                            'min_provider_score' => 70,
                            'slippage_buffer_enabled' => false,
                            'dynamic_lot_enabled' => false,
                        ]);
                        
                        $settings = array_merge($settings, $validated);
                        \Illuminate\Support\Facades\Cache::put('smart_risk_settings_' . auth()->id(), $settings, now()->addYear());
                        
                        return back()->with('notify', NotificationHelper::success('Smart Risk settings updated successfully.', 'Success'));
                    } catch (\Exception $e) {
                        \Log::error('SRM settings update error: ' . $e->getMessage());
                        return back()->with('notify', NotificationHelper::error('Failed to update settings. Please try again.', 'Error'));
                    }
                })->name('settings.update');
            });
        }
