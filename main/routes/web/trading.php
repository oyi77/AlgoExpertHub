<?php

use App\Http\Controllers\CryptoTradeController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Helpers\NotificationHelper;
/*
|--------------------------------------------------------------------------
| Trading Routes
|--------------------------------------------------------------------------
|
| All trading-related routes including unified trading pages, trading
| management addon routes, signals, plans, legacy trade, and terminal.
|
*/

Route::name('user.')->middleware(['auth', 'inactive', 'is_email_verified', '2fa', 'kyc'])->group(function () {
    Route::middleware('check_onboarding')->group(function () {
        Route::get('dashboard', [UserController::class, 'dashboard'])->name('dashboard');

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

        // Help Center
        Route::prefix('help')->name('help.')->group(function () {
            Route::get('/', [\App\Http\Controllers\User\HelpController::class, 'index'])->name('index');
            Route::get('/topic/{topic}', [\App\Http\Controllers\User\HelpController::class, 'topic'])->name('topic');
        });

        // ============================================
        // BACKWARD COMPATIBILITY REDIRECTS
        // Old routes redirect to new unified pages
        // ============================================

        // Multi-Channel Signal Addon - Old routes
        Route::get('external-signals', function() {
            return redirect()->route('user.trading.multi-channel-signal.index', ['tab' => 'signal-sources']);
        })->name('external-signals.index');

        Route::get('signal-sources', function() {
            return redirect()->route('user.trading.multi-channel-signal.index', ['tab' => 'signal-sources']);
        })->name('signal-sources.index');

        Route::get('channel-forwarding', function() {
            return redirect()->route('user.trading.multi-channel-signal.index', ['tab' => 'channel-forwarding']);
        })->name('channel-forwarding.index');

        // Trading Management - Old routes
        Route::get('execution-connections', function() {
            return redirect()->route('user.trading.operations.index', ['tab' => 'connections']);
        })->name('execution-connections.index');

        Route::get('trading-presets', function() {
            return redirect()->route('user.trading.configuration.index', ['tab' => 'risk-presets']);
        })->name('trading-presets.index');

        Route::get('filter-strategies', function() {
            return redirect()->route('user.trading.configuration.index', ['tab' => 'filter-strategies']);
        })->name('filter-strategies.index');

        Route::get('ai-model-profiles', function() {
            return redirect()->route('user.trading.configuration.index', ['tab' => 'ai-profiles']);
        })->name('ai-model-profiles.index');

        // Trading Management Addon - User Routes (registered at root user. prefix)
        if (\App\Support\AddonRegistry::active('trading-management-addon')) {
            // Trading Presets
            if (\App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'risk_management')) {
                Route::prefix('trading-presets')->name('trading-presets.')->group(function () {
                    Route::get('/', function () {
                        try {
                            $title = 'My Trading Presets';
                            
                            if (!\Schema::hasTable('trading_presets')) {
                                \Log::warning('Trading presets table does not exist');
                                return view('trading-management::user.risk-management.presets.index', [
                                    'presets' => collect([])->paginate(20),
                                    'title' => $title
                                ]);
                            }
                            
                            $presets = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where(function($query) {
                                $query->where('created_by_user_id', auth()->id())
                                      ->orWhereNull('created_by_user_id');
                            })
                            ->orderBy('created_at', 'desc')
                            ->paginate(20);
                            return view('trading-management::user.risk-management.presets.index', compact('presets', 'title'));
                        } catch (\Exception $e) {
                            \Log::error('Trading presets index error: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine()
                            ]);
                            return view('trading-management::user.risk-management.presets.index', [
                                'presets' => collect([])->paginate(20),
                                'title' => 'My Trading Presets'
                            ]);
                        }
                    })->name('index');
                    
                    Route::get('/marketplace', function () {
                        try {
                            $title = 'Trading Presets Marketplace';
                            $presets = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::whereNull('created_by_user_id')
                                ->where('visibility', 'PUBLIC_MARKETPLACE')
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);
                            return view('trading-management::user.risk-management.presets.marketplace', compact('presets', 'title'));
                        } catch (\Exception $e) {
                            \Log::error('Trading presets marketplace error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
                            return view('trading-management::user.risk-management.presets.marketplace', [
                                'presets' => collect([])->paginate(20),
                                'title' => 'Trading Presets Marketplace'
                            ]);
                        }
                    })->name('marketplace');
                    
                    Route::get('/create', function () {
                        $title = 'Create Trading Preset';
                        return view('trading-management::user.risk-management.presets.create', compact('title'));
                    })->name('create');
                    
                    Route::get('/{id}/edit', function ($id) {
                        try {
                            $preset = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::findOrFail($id);
                            
                            if ($preset->created_by_user_id !== auth()->id() && !is_null($preset->created_by_user_id)) {
                                return back()->with('notify', NotificationHelper::error('You can only edit your own presets.', 'Error'));
                            }
                            
                            $title = 'Edit Trading Preset';
                            return view('trading-management::user.risk-management.presets.edit', compact('preset', 'title'));
                        } catch (\Exception $e) {
                            \Log::error('Trading preset edit error: ' . $e->getMessage());
                            return back()->with('notify', NotificationHelper::error('Preset not found.', 'Error'));
                        }
                    })->name('edit');
                    
                    Route::post('/{id}/clone', function ($id) {
                        try {
                            $preset = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::findOrFail($id);
                            
                            if (!$preset->isPublic() || !$preset->isClonable()) {
                                return back()->with('notify', NotificationHelper::error('This preset cannot be cloned.', 'Error'));
                            }
                            
                            if (method_exists($preset, 'cloneFor')) {
                                $clonedPreset = $preset->cloneFor(auth()->user());
                            } else {
                                $clonedPreset = $preset->replicate();
                                $clonedPreset->created_by_user_id = auth()->id();
                                $clonedPreset->is_default_template = false;
                                $clonedPreset->visibility = 'PRIVATE';
                                $clonedPreset->name = $preset->name . ' (Copy)';
                                $clonedPreset->save();
                            }
                            
                            return back()->with('notify', NotificationHelper::success('Preset cloned successfully!', 'Success'));
                        } catch (\Exception $e) {
                            \Log::error('Trading preset clone error: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString()
                            ]);
                            return back()->with('notify', NotificationHelper::error('Failed to clone preset. Please try again.', 'Error'));
                        }
                    })->name('clone');
                });
            }

            // Filter Strategies
            if (\App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'filter_strategy')) {
                Route::prefix('filter-strategies')->name('filter-strategies.')->group(function () {
                    Route::get('/', function () {
                        try {
                            $title = 'My Filter Strategies';
                            
                            if (!\Schema::hasTable('filter_strategies')) {
                                \Log::warning('Filter strategies table does not exist');
                                return view('trading-management::user.filter-strategy.index', [
                                    'strategies' => collect([])->paginate(20),
                                    'title' => $title
                                ]);
                            }
                            
                            $strategies = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::where('created_by_user_id', auth()->id())
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);
                            return view('trading-management::user.filter-strategy.index', compact('strategies', 'title'));
                        } catch (\Exception $e) {
                            \Log::error('Filter strategies index error: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine()
                            ]);
                            return view('trading-management::user.filter-strategy.index', [
                                'strategies' => collect([])->paginate(20),
                                'title' => 'My Filter Strategies'
                            ]);
                        }
                    })->name('index');
                    
                    Route::get('/marketplace', function () {
                        try {
                            $title = 'Filter Strategies Marketplace';
                            
                            if (!\Schema::hasTable('filter_strategies')) {
                                \Log::warning('Filter strategies table does not exist');
                                return view('trading-management::user.filter-strategy.marketplace', [
                                    'strategies' => collect([])->paginate(20),
                                    'title' => $title
                                ]);
                            }
                            
                            $strategies = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::whereNull('created_by_user_id')
                                ->where('visibility', 'PUBLIC_MARKETPLACE')
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);
                            return view('trading-management::user.filter-strategy.marketplace', compact('strategies', 'title'));
                        } catch (\Exception $e) {
                            \Log::error('Filter strategies marketplace error: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine()
                            ]);
                            return view('trading-management::user.filter-strategy.marketplace', [
                                'strategies' => collect([])->paginate(20),
                                'title' => 'Filter Strategies Marketplace'
                            ]);
                        }
                    })->name('marketplace');
                    
                    Route::get('/create', function () {
                        $title = 'Create Filter Strategy';
                        return view('trading-management::user.filter-strategy.create', compact('title'));
                    })->name('create');
                    
                    Route::post('/{id}/clone', function ($id) {
                        try {
                            $strategy = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::findOrFail($id);
                            
                            if (!$strategy->canBeClonedBy(auth()->id())) {
                                return back()->with('notify', NotificationHelper::error('This strategy cannot be cloned.', 'Error'));
                            }
                            
                            $clonedStrategy = $strategy->cloneForUser(auth()->id());
                            
                            return back()->with('notify', NotificationHelper::success('Strategy cloned successfully!', 'Success'));
                        } catch (\Exception $e) {
                            \Log::error('Filter strategy clone error: ' . $e->getMessage());
                            return back()->with('notify', NotificationHelper::error('Failed to clone strategy. Please try again.', 'Error'));
                        }
                    })->name('clone');
                });
            }

            // AI Model Profiles
            if (\App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'ai_analysis')) {
                Route::prefix('ai-model-profiles')->name('ai-model-profiles.')->group(function () {
                    Route::get('/', function () {
                        try {
                            $title = 'My AI Model Profiles';
                            
                            if (!\Schema::hasTable('ai_model_profiles')) {
                                \Log::warning('AI model profiles table does not exist');
                                return view('trading-management::user.ai-analysis.profiles.index', [
                                    'profiles' => collect([])->paginate(20),
                                    'title' => $title
                                ]);
                            }
                            
                            $profiles = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::where('created_by_user_id', auth()->id())
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);
                            return view('trading-management::user.ai-analysis.profiles.index', compact('profiles', 'title'));
                        } catch (\Exception $e) {
                            \Log::error('AI model profiles index error: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine()
                            ]);
                            return view('trading-management::user.ai-analysis.profiles.index', [
                                'profiles' => collect([])->paginate(20),
                                'title' => 'My AI Model Profiles'
                            ]);
                        }
                    })->name('index');
                    
                    Route::get('/marketplace', function () {
                        try {
                            $title = 'AI Model Profiles Marketplace';
                            
                            if (!\Schema::hasTable('ai_model_profiles')) {
                                \Log::warning('AI model profiles table does not exist');
                                return view('trading-management::user.ai-analysis.profiles.marketplace', [
                                    'profiles' => collect([])->paginate(20),
                                    'title' => $title
                                ]);
                            }
                            
                            $profiles = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::whereNull('created_by_user_id')
                                ->where('visibility', 'PUBLIC_MARKETPLACE')
                                ->orderBy('created_at', 'desc')
                                ->paginate(20);
                            return view('trading-management::user.ai-analysis.profiles.marketplace', compact('profiles', 'title'));
                        } catch (\Exception $e) {
                            \Log::error('AI model profiles marketplace error: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine()
                            ]);
                            return view('trading-management::user.ai-analysis.profiles.marketplace', [
                                'profiles' => collect([])->paginate(20),
                                'title' => 'AI Model Profiles Marketplace'
                            ]);
                        }
                    })->name('marketplace');
                    
                    Route::get('/create', function () {
                        $title = 'Create AI Model Profile';
                        return view('trading-management::user.ai-analysis.profiles.create', compact('title'));
                    })->name('create');
                    
                    Route::post('/{id}/clone', function ($id) {
                        try {
                            $profile = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::findOrFail($id);
                            
                            if (!$profile->canBeClonedBy(auth()->id())) {
                                return back()->with('notify', NotificationHelper::error('This AI profile cannot be cloned.', 'Error'));
                            }
                            
                            $clonedProfile = $profile->replicate();
                            $clonedProfile->created_by_user_id = auth()->id();
                            $clonedProfile->visibility = 'PRIVATE';
                            $clonedProfile->name = $profile->name . ' (Copy)';
                            $clonedProfile->save();
                            
                            return back()->with('notify', NotificationHelper::success('AI profile cloned successfully!', 'Success'));
                        } catch (\Exception $e) {
                            \Log::error('AI model profile clone error: ' . $e->getMessage());
                            return back()->with('notify', NotificationHelper::error('Failed to clone AI profile. Please try again.', 'Error'));
                        }
                    })->name('clone');
                });
            }

            // Copy Trading
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
            
            // Exchange Connections (Data Connections for users)
            if (\App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'exchange_connection')) {
                Route::prefix('exchange-connections')->name('exchange-connections.')->group(function () {
                    Route::get('/create', function () {
                        try {
                            $title = 'Create Data Connection';
                            $presets = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where(function($query) {
                                $query->where('created_by_user_id', auth()->id())
                                      ->orWhereNull('created_by_user_id');
                            })->get();
                            
                            return view('trading-management::user.exchange-connections.create', compact('title', 'presets'));
                        } catch (\Exception $e) {
                            \Log::error('Exchange connection create error: ' . $e->getMessage());
                            return back()->with('notify', NotificationHelper::error('Failed to load create form.', 'Error'));
                        }
                    })->name('create');

                    Route::get('/{exchangeConnection}/edit', function ($id) {
                        try {
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                ->where('user_id', auth()->id())
                                ->where('is_admin_owned', false)
                                ->firstOrFail();
                            
                            $title = 'Edit Data Connection';
                            $presets = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where(function($query) {
                                $query->where('created_by_user_id', auth()->id())
                                      ->orWhereNull('created_by_user_id');
                            })->get();
                            
                            return view('trading-management::user.exchange-connections.edit', compact('title', 'connection', 'presets'));
                        } catch (\Exception $e) {
                            \Log::error('Exchange connection edit error: ' . $e->getMessage());
                            return redirect()->route('user.trading.configuration.index', ['tab' => 'data-connections'])
                                ->with('notify', NotificationHelper::error('Failed to load edit form.', 'Error'));
                        }
                    })->name('edit');

                    Route::put('/{exchangeConnection}', function (\Illuminate\Http\Request $request, $id) {
                        try {
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                ->where('user_id', auth()->id())
                                ->where('is_admin_owned', false)
                                ->firstOrFail();
                            
                            $validated = $request->validate([
                                'name' => 'required|string|max:255',
                                'preset_id' => 'nullable|exists:trading_presets,id',
                                'connection_type' => 'required|in:DATA_ONLY,EXECUTION_ONLY,BOTH',
                                'credentials' => 'nullable|array',
                            ]);
                            
                            $connection->name = $validated['name'];
                            $connection->preset_id = $validated['preset_id'];
                            $connection->connection_type = $validated['connection_type'];
                            
                            // If credentials are provided, update only non-empty fields
                            if (!empty($validated['credentials'])) {
                                $currentCredentials = $connection->credentials ?? [];
                                foreach ($validated['credentials'] as $key => $value) {
                                    if (!empty($value)) {
                                        $currentCredentials[$key] = $value;
                                    }
                                }
                                $connection->credentials = $currentCredentials;
                            }
                            
                            $connection->save();
                            
                            if ($request->ajax() || $request->wantsJson()) {
                                return response()->json([
                                    'success' => true,
                                    'message' => NotificationHelper::success('Connection updated successfully', 'Success'),
                                    'redirect' => route('user.exchange-connections.show', $connection->id)
                                ]);
                            }
                            
                            return redirect()->route('user.exchange-connections.show', $connection->id)
                                ->with('notify', NotificationHelper::success('Connection updated successfully', 'Success'));
                        } catch (\Exception $e) {
                            \Log::error('Exchange connection update error: ' . $e->getMessage());
                            
                            if ($request->ajax() || $request->wantsJson()) {
                                return response()->json(['success' => false, 'message' => NotificationHelper::error('Failed to update connection: ' . $e->getMessage(), 'Error')], 500);
                            }
                            
                            return back()->with('notify', NotificationHelper::error('Failed to update connection.', 'Error'))->withInput();
                        }
                    })->name('update');

                    Route::delete('/{exchangeConnection}', function (\Illuminate\Http\Request $request, $id) {
                        try {
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                ->where('user_id', auth()->id())
                                ->where('is_admin_owned', false)
                                ->firstOrFail();
                            
                            $connection->delete();
                            
                            if ($request->ajax() || $request->wantsJson()) {
                                return response()->json([
                                    'success' => true,
                                    'message' => NotificationHelper::success('Connection deleted successfully', 'Success'),
                                    'redirect' => route('user.trading.configuration.index', ['tab' => 'data-connections'])
                                ]);
                            }
                            
                            return redirect()->route('user.trading.configuration.index', ['tab' => 'data-connections'])
                                ->with('notify', NotificationHelper::success('Connection deleted successfully', 'Success'));
                        } catch (\Exception $e) {
                            \Log::error('Exchange connection delete error: ' . $e->getMessage());
                            return response()->json(['success' => false, 'message' => NotificationHelper::error('Failed to delete connection.', 'Error')], 500);
                        }
                    })->name('destroy');

                    Route::get('/ccxt-exchanges', function () {
                        try {
                            $service = new \Addons\TradingManagement\Modules\ExchangeConnection\Services\CcxtExchangeService();
                            $exchanges = $service->getCryptoExchanges();
                            
                            return response()->json([
                                'success' => true,
                                'exchanges' => $exchanges,
                                'count' => count($exchanges)
                            ]);
                        } catch (\Exception $e) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Failed to load exchanges: ' . $e->getMessage(),
                                'exchanges' => []
                            ], 500);
                        }
                    })->name('ccxt-exchanges');

                    // Test connection from form data (before saving)
                    Route::post('/test-connection', function (\Illuminate\Http\Request $request) {
                        try {
                            $validated = $request->validate([
                                'exchange_type' => 'required|in:CRYPTO_EXCHANGE,FX_BROKER',
                                'exchange_name' => 'required|string',
                                'connection_type' => 'required|in:DATA_ONLY,EXECUTION_ONLY,BOTH',
                                'credentials' => 'required|array',
                                'credentials.api_key' => 'nullable|string',
                                'credentials.api_secret' => 'nullable|string',
                                'credentials.api_passphrase' => 'nullable|string',
                                'credentials.account_id' => 'nullable|string',
                            ]);

                            // Create temporary connection object for testing
                            $tempConnection = new \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection([
                                'exchange_type' => $validated['exchange_type'],
                                'connection_type' => $validated['connection_type'],
                                'provider' => $validated['exchange_name'],
                                'exchange_name' => $validated['exchange_name'],
                                'credentials' => $validated['credentials'],
                            ]);

                            // Get adapter and test
                            $service = new \Addons\TradingManagement\Modules\ExchangeConnection\Services\ExchangeConnectionService();
                            $adapter = $service->getAdapter($tempConnection);

                            if (!$adapter) {
                                return response()->json([
                                    'success' => false,
                                    'message' => NotificationHelper::error('Unsupported exchange or provider', 'Error')
                                ], 400);
                            }

                            // Test connection based on provider
                            if ($validated['exchange_name'] === 'metaapi') {
                                if (empty($validated['credentials']['account_id'])) {
                                    return response()->json([
                                        'success' => false,
                                        'message' => NotificationHelper::error('MetaApi Account ID is required', 'Error')
                                    ], 400);
                                }
                                
                                if (method_exists($adapter, 'testConnection')) {
                                    $result = $adapter->testConnection();
                                    return response()->json($result);
                                }
                                
                                if (method_exists($adapter, 'getAccountInfo')) {
                                    $accountInfo = $adapter->getAccountInfo();
                                    return response()->json([
                                        'success' => true,
                                        'message' => NotificationHelper::success('Connection test successful', 'Success'),
                                        'data' => ['account_info' => $accountInfo]
                                    ]);
                                }
                            } else {
                                // For crypto exchanges, try to fetch balance
                                if (method_exists($adapter, 'fetchBalance')) {
                                    try {
                                        $balance = $adapter->fetchBalance();
                                        return response()->json([
                                            'success' => true,
                                            'message' => NotificationHelper::success('Connection test successful', 'Success'),
                                            'data' => ['balance' => $balance]
                                        ]);
                                    } catch (\Exception $e) {
                                        return response()->json([
                                            'success' => false,
                                            'message' => NotificationHelper::error('Failed to connect: ' . $e->getMessage(), 'Error')
                                        ], 400);
                                    }
                                }
                                
                                // Fallback: just verify adapter was created
                                return response()->json([
                                    'success' => true,
                                    'message' => NotificationHelper::success('Connection test completed (credentials accepted)', 'Success')
                                ]);
                            }

                            return response()->json([
                                'success' => false,
                                'message' => NotificationHelper::error('Connection test method not available for this provider', 'Error')
                            ], 400);

                        } catch (\Illuminate\Validation\ValidationException $e) {
                            return response()->json([
                                'success' => false,
                                'message' => NotificationHelper::error('Validation failed', 'Error'),
                                'errors' => $e->errors()
                            ], 422);
                        } catch (\Exception $e) {
                            \Log::error('Test connection error', [
                                'error' => $e->getMessage(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => NotificationHelper::error('Connection test failed: ' . $e->getMessage(), 'Error')
                            ], 500);
                        }
                    })->name('test-connection');

                    Route::get('/{exchangeConnection}', function ($id) {
                        try {
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                ->where('user_id', auth()->id())
                                ->where('is_admin_owned', false)
                                ->firstOrFail();
                            
                            $title = 'Exchange Connection - ' . $connection->name;
                            
                            if (view()->exists('trading-management::user.exchange-connections.show')) {
                                return view('trading-management::user.exchange-connections.show', compact('title', 'connection'));
                            } else {
                                return redirect()->route('user.trading.operations.index', ['tab' => 'connections'])
                                    ->with('info', NotificationHelper::info('Connection details: ' . $connection->name, 'Info'));
                            }
                        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                            return redirect()->route('user.trading.operations.index', ['tab' => 'connections'])
                                ->with('notify', NotificationHelper::error('Connection not found or you do not have permission to view it.', 'Error'));
                        } catch (\Exception $e) {
                            \Log::error('Exchange connection show error: ' . $e->getMessage());
                            return redirect()->route('user.trading.operations.index', ['tab' => 'connections'])
                                ->with('notify', NotificationHelper::error('Failed to load connection details.', 'Error'));
                        }
                    })->name('show');
                    
                    Route::post('/{exchangeConnection}/test', function ($id) {
                        try {
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                ->where('user_id', auth()->id())
                                ->where('is_admin_owned', false)
                                ->firstOrFail();
                            
                            $controller = app(\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class);
                            return $controller->testConnection($connection);
                        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                            return response()->json([
                                'success' => false,
                                'message' => NotificationHelper::error('Connection not found or you do not have permission to test it.', 'Error')
                            ], 404);
                        } catch (\Exception $e) {
                            \Log::error('Exchange connection test error: ' . $e->getMessage(), [
                                'trace' => $e->getTraceAsString()
                            ]);
                            return response()->json([
                                'success' => false,
                                'message' => NotificationHelper::error('Failed to test connection: ' . $e->getMessage(), 'Error')
                            ], 500);
                        }
                    })->name('test');
                    
                    // Activate/Deactivate connection
                    Route::post('/{exchangeConnection}/toggle-activation', function ($id) {
                        try {
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                ->where('user_id', auth()->id())
                                ->where('is_admin_owned', false)
                                ->firstOrFail();
                            
                            $connection->is_active = !$connection->is_active;
                            $connection->save();
                            
                            return response()->json([
                                'success' => true,
                                'is_active' => $connection->is_active,
                                'message' => $connection->is_active 
                                    ? NotificationHelper::success('Connection activated successfully', 'Success') 
                                    : __('Connection deactivated successfully')
                            ]);
                        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                            return response()->json(['success' => false, 'message' => NotificationHelper::error('Connection not found.', 'Error')], 404);
                        } catch (\Exception $e) {
                            \Log::error('Connection activation toggle error: ' . $e->getMessage());
                            return response()->json(['success' => false, 'message' => NotificationHelper::error('Failed to toggle activation.', 'Error')], 500);
                        }
                    })->name('toggle-activation');
                    
                    // Toggle copy trading
                    Route::post('/{exchangeConnection}/toggle-copy-trading', function ($id) {
                        try {
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                ->where('user_id', auth()->id())
                                ->where('is_admin_owned', false)
                                ->firstOrFail();
                            
                            $hasSubscription = auth()->user()->currentplan()->exists();
                            if (!$hasSubscription) {
                                return response()->json(['success' => false, 'message' => NotificationHelper::error('Active subscription required.', 'Error')], 403);
                            }
                            
                            $connection->copy_trading_enabled = !$connection->copy_trading_enabled;
                            $connection->save();
                            
                            return response()->json([
                                'success' => true,
                                'copy_trading_enabled' => $connection->copy_trading_enabled,
                                'message' => $connection->copy_trading_enabled 
                                    ? NotificationHelper::success('Copy trading enabled', 'Success') 
                                    : NotificationHelper::success('Copy trading disabled', 'Success')
                            ]);
                        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                            return response()->json(['success' => false, 'message' => NotificationHelper::error('Connection not found.', 'Error')], 404);
                        } catch (\Exception $e) {
                            \Log::error('Copy trading toggle error: ' . $e->getMessage());
                            return response()->json(['success' => false, 'message' => NotificationHelper::error('Failed to toggle copy trading.', 'Error')], 500);
                        }
                    })->name('toggle-copy-trading');
                    
                    // Update purpose
                    Route::post('/{exchangeConnection}/update-purpose', function (\Illuminate\Http\Request $request, $id) {
                        try {
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                ->where('user_id', auth()->id())
                                ->where('is_admin_owned', false)
                                ->firstOrFail();
                            
                            $request->validate([
                                'data_fetching_enabled' => 'sometimes|boolean',
                                'trade_execution_enabled' => 'sometimes|boolean',
                            ]);
                            
                            if ($request->has('data_fetching_enabled')) {
                                $connection->data_fetching_enabled = $request->boolean('data_fetching_enabled');
                            }
                            if ($request->has('trade_execution_enabled')) {
                                $connection->trade_execution_enabled = $request->boolean('trade_execution_enabled');
                            }
                            
                            $connection->save();
                            
                            return response()->json([
                                'success' => true,
                                'data_fetching_enabled' => $connection->data_fetching_enabled,
                                'trade_execution_enabled' => $connection->trade_execution_enabled,
                                'message' => NotificationHelper::success('Purpose updated successfully', 'Success')
                            ]);
                        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                            return response()->json(['success' => false, 'message' => NotificationHelper::error('Connection not found.', 'Error')], 404);
                        } catch (\Exception $e) {
                            \Log::error('Purpose update error: ' . $e->getMessage());
                            return response()->json(['success' => false, 'message' => NotificationHelper::error('Failed to update purpose.', 'Error')], 500);
                        }
                    })->name('update-purpose');
                    
                    // MetaApi provisioning endpoints for users
                    Route::post('/add-metaapi-account', function (\Illuminate\Http\Request $request) {
                        try {
                            $controller = app(\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class);
                            return $controller->addMetaApiAccount($request);
                        } catch (\Exception $e) {
                            \Log::error('MetaApi add account error: ' . $e->getMessage());
                            return response()->json([
                                'success' => false,
                                'message' => NotificationHelper::error('Failed to add account: ' . $e->getMessage(), 'Error')
                            ], 500);
                        }
                    })->name('add-metaapi-account');
                    
                    Route::post('/metaapi-account-status', function (\Illuminate\Http\Request $request) {
                        try {
                            $controller = app(\Addons\TradingManagement\Modules\ExchangeConnection\Controllers\Backend\ExchangeConnectionController::class);
                            return $controller->getMetaApiAccountStatus($request);
                        } catch (\Exception $e) {
                            \Log::error('MetaApi account status error: ' . $e->getMessage());
                            return response()->json([
                                'success' => false,
                                'message' => NotificationHelper::error('Failed to get account status: ' . $e->getMessage(), 'Error')
                            ], 500);
                        }
                    })->name('metaapi-account-status');
                    
                    Route::post('/', function (\Addons\TradingManagement\Modules\ExchangeConnection\Http\Requests\StoreExchangeConnectionRequest $request) {
                        $isAjax = $request->ajax() || $request->wantsJson() || $request->expectsJson();
                        
                        // Additional auth check for safety
                        if (!auth()->check()) {
                            \Log::warning('Exchange connection store: User not authenticated', [
                                'ip' => $request->ip(),
                                'user_agent' => $request->userAgent(),
                                'url' => $request->fullUrl()
                            ]);
                            
                            if ($isAjax) {
                                return response()->json([
                                    'success' => false,
                                    'message' => NotificationHelper::error('Your session has expired. Please log in again.', 'Error'),
                                    'redirect' => route('user.login')
                                ], 401);
                            }
                            
                            return redirect()->route('user.login')
                                ->with('notify', NotificationHelper::error('Your session has expired. Please log in again.', 'Error'));
                        }

                        // Check if user is active
                        if (auth()->user()->status != 1) {
                            \Log::warning('Exchange connection store: User account inactive', [
                                'user_id' => auth()->id()
                            ]);
                            
                            if ($isAjax) {
                                return response()->json([
                                    'success' => false,
                                    'message' => NotificationHelper::error('Your account is inactive. Please contact support.', 'Error')
                                ], 403);
                            }
                            
                            return redirect()->back()
                                ->with('notify', NotificationHelper::error('Your account is inactive. Please contact support.', 'Error'));
                        }

                        try {
                            $validated = $request->validated();
                            
                            \Log::info('Exchange connection store: Processing request', [
                                'user_id' => auth()->id(),
                                'connection_name' => $validated['name'] ?? 'unknown',
                                'exchange_type' => $validated['exchange_type'] ?? 'unknown',
                                'provider' => $validated['exchange_name'] ?? 'unknown'
                            ]);
                            
                            // Validate credentials based on provider
                            if ($validated['exchange_name'] === 'metaapi') {
                                if (empty($validated['credentials']['account_id'])) {
                                    $errorMessage = NotificationHelper::error('MetaApi Account ID is required. Add your MT account to MetaApi first, then copy the Account ID from your MetaApi dashboard.', 'Error');
                                    
                                    if ($isAjax) {
                                        return response()->json([
                                            'success' => false,
                                            'message' => $errorMessage,
                                            'errors' => ['credentials.account_id' => [$errorMessage]]
                                        ], 422);
                                    }
                                    
                                    return redirect()->back()
                                        ->withInput()
                                        ->withErrors(['credentials.account_id' => $errorMessage]);
                                }
                                // Auto-fill api_token from config if not provided
                                if (empty($validated['credentials']['api_token'])) {
                                    $validated['credentials']['api_token'] = config('trading-management.metaapi.api_token');
                                }
                            }
                            
                            // Map exchange_type to type column (crypto/fx)
                            $typeMapping = [
                                'CRYPTO_EXCHANGE' => 'crypto',
                                'FX_BROKER' => 'fx',
                            ];
                            
                            // Don't manually encrypt - the HasEncryptedCredentials trait handles this
                            $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::create([
                                'user_id' => auth()->id(),
                                'is_admin_owned' => false,
                                'name' => $validated['name'],
                                'type' => $typeMapping[$validated['exchange_type']] ?? 'crypto',
                                'connection_type' => $validated['exchange_type'], // CRYPTO_EXCHANGE or FX_BROKER
                                'provider' => $validated['exchange_name'],
                                'exchange_name' => $validated['exchange_name'],
                                'credentials' => $validated['credentials'], // Trait will encrypt this
                                'preset_id' => $validated['preset_id'] ?? null,
                                'is_active' => false,
                                'status' => 'PENDING_TEST',
                                'data_fetching_enabled' => $validated['connection_type'] === 'DATA_ONLY' || $validated['connection_type'] === 'BOTH',
                                'trade_execution_enabled' => $validated['connection_type'] === 'EXECUTION_ONLY' || $validated['connection_type'] === 'BOTH',
                            ]);
                            
                            \Log::info('Exchange connection store: Connection created successfully', [
                                'user_id' => auth()->id(),
                                'connection_id' => $connection->id,
                                'connection_name' => $connection->name
                            ]);
                            
                            if ($isAjax) {
                                return response()->json([
                                    'success' => true,
                                    'message' => NotificationHelper::success('Data connection created successfully.', 'Success'),
                                    'redirect' => route('user.trading.configuration.index', ['tab' => 'data-connections'])
                                ], 201);
                            }
                            
                            return redirect()->route('user.trading.configuration.index', ['tab' => 'data-connections'])
                                ->with('notify', NotificationHelper::success('Data connection created successfully.', 'Success'));
                        } catch (\Illuminate\Validation\ValidationException $e) {
                            \Log::warning('Exchange connection store: Validation failed', [
                                'user_id' => auth()->id(),
                                'errors' => $e->errors()
                            ]);
                            
                            if ($isAjax) {
                                return response()->json([
                                    'success' => false,
                                    'message' => NotificationHelper::error('Validation failed. Please check your input.', 'Error'),
                                    'errors' => $e->errors()
                                ], 422);
                            }
                            
                            return back()->withErrors($e->errors())->withInput();
                        } catch (\Illuminate\Database\QueryException $e) {
                            \Log::error('Exchange connection store: Database error', [
                                'user_id' => auth()->id(),
                                'error' => $e->getMessage(),
                                'code' => $e->getCode()
                            ]);
                            
                            $errorMessage = NotificationHelper::error('Database error occurred. Please try again or contact support if the problem persists.', 'Error');
                            
                            if ($isAjax) {
                                return response()->json([
                                    'success' => false,
                                    'message' => $errorMessage
                                ], 500);
                            }
                            
                            return back()
                                ->with('notify', NotificationHelper::error('Database error occurred. Please try again or contact support if the problem persists.', 'Error'))
                                ->withInput();
                        } catch (\Exception $e) {
                            \Log::error('Exchange connection store error', [
                                'user_id' => auth()->id(),
                                'error' => $e->getMessage(),
                                'file' => $e->getFile(),
                                'line' => $e->getLine(),
                                'trace' => $e->getTraceAsString()
                            ]);
                            
                            $errorMessage = NotificationHelper::error('Failed to create connection. Please try again. If the problem persists, contact support.', 'Error')->withInput();
                            
                            if ($isAjax) {
                                return response()->json([
                                    'success' => false,
                                    'message' => $errorMessage
                                ], 500);
                            }
                            
                            return back()
                                ->with('notify', $errorMessage)
                                ->withInput();
                        }
                    })->name('store');
                });
            }
        }

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
    });
});

