<?php

use App\Http\Controllers\Auth\FacebookController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\RegistrationController;
use App\Http\Controllers\CryptoTradeController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\Gateway\paystack\ProcessController;
use App\Http\Controllers\KycController;
use App\Http\Controllers\LogController;
use App\Http\Controllers\LoginSecurityController;
use App\Http\Controllers\MoneyTransferController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayoutController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\SignalController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\User\ExternalSignalController;
use App\Http\Controllers\WithdrawController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('admin', function () {
    return redirect()->route('admin.login');
});

// Health check endpoint for monitoring
Route::get('/health', function () {
    $status = [
        'status' => 'ok',
        'timestamp' => now()->toIso8601String(),
        'checks' => []
    ];

    // Database check
    try {
        \DB::connection()->getPdo();
        $status['checks']['database'] = 'connected';
    } catch (\Exception $e) {
        $status['checks']['database'] = 'disconnected';
        $status['status'] = 'degraded';
    }

    // Queue check
    try {
        $queueSize = \Queue::size();
        $status['checks']['queue'] = [
            'status' => 'ok',
            'pending_jobs' => $queueSize
        ];
    } catch (\Exception $e) {
        $status['checks']['queue'] = [
            'status' => 'error',
            'error' => $e->getMessage()
        ];
        $status['status'] = 'degraded';
    }

    // Cache check
    try {
        $cacheDriver = config('cache.default');
        \Cache::put('health_check', 'ok', 10);
        $cacheValue = \Cache::get('health_check');
        $status['checks']['cache'] = [
            'status' => $cacheValue === 'ok' ? 'working' : 'error',
            'driver' => $cacheDriver
        ];
        if ($cacheValue !== 'ok') {
            $status['status'] = 'degraded';
        }
    } catch (\Exception $e) {
        $status['checks']['cache'] = [
            'status' => 'error',
            'driver' => config('cache.default', 'unknown'),
            'error' => $e->getMessage()
        ];
        $status['status'] = 'degraded';
    }

    // Octane check (if available)
    if (class_exists(\Laravel\Octane\Octane::class)) {
        $status['checks']['octane'] = 'available';
    } else {
        $status['checks']['octane'] = 'not_installed';
    }

    $httpStatus = $status['status'] === 'ok' ? 200 : 503;
    return response()->json($status, $httpStatus);
})->name('health');

Route::name('user.')->group(function () {

    Route::middleware('guest')->group(function () {
        Route::get('register/{reffer?}', [RegistrationController::class, 'index'])->name('register')->middleware('reg_off');
        Route::post('register/{reffer?}', [RegistrationController::class, 'register'])->middleware('reg_off');

        Route::get('login', [LoginController::class, 'index'])->name('login');
        Route::post('login', [LoginController::class, 'login']);

        Route::get('auth/facebook', [FacebookController::class, 'redirectToFacebook'])->name('facebook.login');
        Route::get('auth/facebook/callback', [FacebookController::class, 'handleFacebookCallback']);


        Route::get('auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google.login');
        Route::get('auth/google/callback', [GoogleController::class, 'handleGoogleCallback']);

        Route::get('forgot/password', [ForgotPasswordController::class, 'index'])->name('forgot.password');
        Route::post('forgot/password', [ForgotPasswordController::class, 'sendVerification']);
        Route::get('verify/code', [ForgotPasswordController::class, 'verify'])->name('auth.verify');
        Route::post('verify/code', [ForgotPasswordController::class, 'verifyCode']);
        Route::get('reset/password', [ForgotPasswordController::class, 'reset'])->name('reset.password');
        Route::post('reset/password', [ForgotPasswordController::class, 'resetPassword']);

        Route::get('verify/email', [LoginController::class, 'emailVerify'])->name('email.verify');
        Route::post('verify/email', [LoginController::class, 'emailVerifyConfirm'])->name('email.verify.confirm');
    });

    Route::middleware(['auth', 'inactive', 'is_email_verified'])->group(function () {

        Route::get('2fa', [LoginSecurityController::class, 'show2faForm'])->name('2fa');
        Route::post('2fa/generateSecret', [LoginSecurityController::class, 'generate2faSecret'])->name('generate2faSecret');
        Route::post('2fa/enable2fa', [LoginSecurityController::class, 'enable2fa'])->name('enable2fa');
        Route::post('2fa/disable2fa', [LoginSecurityController::class, 'disable2fa'])->name('disable2fa');
        Route::post('2fa/2faVerify', function () {
            return redirect(URL()->previous());
        })->name('2faVerify')->middleware('2fa');

        Route::get('authentication-verify', [ForgotPasswordController::class, 'verifyAuth'])->name('authentication.verify')->withoutMiddleware('is_email_verified');

        Route::post('authentication-verify/email', [ForgotPasswordController::class, 'verifyEmailAuth'])->name('authentication.verify.email')->withoutMiddleware('is_email_verified');

        Route::post('authentication-verify/sms', [ForgotPasswordController::class, 'verifySmsAuth'])->name('authentication.verify.sms')->withoutMiddleware('is_email_verified');

        Route::get('logout', [LoginController::class, 'signOut'])->name('logout');

        Route::get('kyc', [KycController::class, 'kyc'])->name('kyc');
        Route::post('kyc', [KycController::class, 'kycUpdate']);


        Route::middleware('2fa', 'kyc')->group(function () {

            // Onboarding routes (must be before check_onboarding to allow onboarding access)
            Route::prefix('onboarding')->name('onboarding.')->group(function () {
                Route::get('/welcome', [\App\Http\Controllers\User\OnboardingController::class, 'welcome'])->name('welcome');
                Route::post('/welcome', [\App\Http\Controllers\User\OnboardingController::class, 'completeWelcome'])->name('welcome.complete');
                Route::get('/step/{step}', [\App\Http\Controllers\User\OnboardingController::class, 'step'])->name('step');
                Route::post('/step/{step}', [\App\Http\Controllers\User\OnboardingController::class, 'completeStep'])->name('step.complete');
                Route::post('/skip', [\App\Http\Controllers\User\OnboardingController::class, 'skip'])->name('skip');
                Route::get('/complete', [\App\Http\Controllers\User\OnboardingController::class, 'complete'])->name('complete');
            });

            // Apply onboarding check to all other routes
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
                });

                // Configurations (sub menu from Trading Operations)
                Route::prefix('configurations')->name('configurations.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\User\Trading\TradingConfigurationsController::class, 'index'])->name('index');
                });

                // Trading Configuration (unified page with tabs)
                Route::prefix('configuration')->name('configuration.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\User\Trading\TradingConfigurationController::class, 'index'])->name('index');
                });

                // Backtesting (unified page with tabs)
                Route::prefix('backtesting')->name('backtesting.')->group(function () {
                    Route::get('/', [\App\Http\Controllers\User\Trading\BacktestingController::class, 'index'])->name('index');
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
                                
                                // Check if table exists
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
                                
                                // Check if user can edit
                                if ($preset->created_by_user_id !== auth()->id() && !is_null($preset->created_by_user_id)) {
                                    return back()->with('error', __('You can only edit your own presets.'));
                                }
                                
                                $title = 'Edit Trading Preset';
                                return view('trading-management::user.risk-management.presets.edit', compact('preset', 'title'));
                            } catch (\Exception $e) {
                                \Log::error('Trading preset edit error: ' . $e->getMessage());
                                return back()->with('error', __('Preset not found.'));
                            }
                        })->name('edit');
                        
                        Route::post('/{id}/clone', function ($id) {
                            try {
                                $preset = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::findOrFail($id);
                                
                                // Check if preset can be cloned
                                if (!$preset->isPublic() || !$preset->isClonable()) {
                                    return back()->with('error', __('This preset cannot be cloned.'));
                                }
                                
                                // Clone preset for current user
                                if (method_exists($preset, 'cloneFor')) {
                                    $clonedPreset = $preset->cloneFor(auth()->user());
                                } else {
                                    // Fallback: manual clone
                                    $clonedPreset = $preset->replicate();
                                    $clonedPreset->created_by_user_id = auth()->id();
                                    $clonedPreset->is_default_template = false;
                                    $clonedPreset->visibility = 'PRIVATE';
                                    $clonedPreset->name = $preset->name . ' (Copy)';
                                    $clonedPreset->save();
                                }
                                
                                return back()->with('success', __('Preset cloned successfully!'));
                            } catch (\Exception $e) {
                                \Log::error('Trading preset clone error: ' . $e->getMessage(), [
                                    'trace' => $e->getTraceAsString()
                                ]);
                                return back()->with('error', __('Failed to clone preset. Please try again.'));
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
                                
                                // Check if table exists
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
                                
                                // Check if table exists
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
                                    return back()->with('error', __('This strategy cannot be cloned.'));
                                }
                                
                                $clonedStrategy = $strategy->cloneForUser(auth()->id());
                                
                                return back()->with('success', __('Strategy cloned successfully!'));
                            } catch (\Exception $e) {
                                \Log::error('Filter strategy clone error: ' . $e->getMessage());
                                return back()->with('error', __('Failed to clone strategy. Please try again.'));
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
                                
                                // Check if table exists
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
                                
                                // Check if table exists
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
                                    return back()->with('error', __('This AI profile cannot be cloned.'));
                                }
                                
                                // Clone using replicate
                                $clonedProfile = $profile->replicate();
                                $clonedProfile->created_by_user_id = auth()->id();
                                $clonedProfile->visibility = 'PRIVATE';
                                $clonedProfile->name = $profile->name . ' (Copy)';
                                $clonedProfile->save();
                                
                                return back()->with('success', __('AI profile cloned successfully!'));
                            } catch (\Exception $e) {
                                \Log::error('AI model profile clone error: ' . $e->getMessage());
                                return back()->with('error', __('Failed to clone AI profile. Please try again.'));
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
                                
                                // Check if table exists
                                if (!\Schema::hasTable('copy_trading_settings')) {
                                    \Log::warning('Copy trading settings table does not exist');
                                    return view('trading-management::user.copy-trading.settings', [
                                        'title' => $title,
                                        'error' => 'Copy trading settings table does not exist. Please run migrations.'
                                    ]);
                                }
                                
                                // Try to get or create settings
                                // Check both deprecated addon and trading-management-addon models
                                $setting = null;
                                try {
                                    // Try deprecated addon model first
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
                                
                                // Get stats if available
                                $stats = [
                                    'follower_count' => 0,
                                    'total_copied_trades' => 0,
                                ];
                                
                                try {
                                    // Try trading-management-addon model first
                                    $subscriptionModel = \Addons\TradingManagement\Modules\CopyTrading\Models\CopyTradingSubscription::class;
                                    if (class_exists($subscriptionModel)) {
                                        $stats['follower_count'] = $subscriptionModel::where('trader_id', auth()->id())
                                            ->where('is_active', true)
                                            ->count();
                                    } elseif (class_exists(\Addons\CopyTrading\App\Models\CopyTradingSubscription::class)) {
                                        // Fallback to deprecated addon
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
                                
                                // Check if table exists
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
                                
                                // Check if table exists
                                if (!\Schema::hasTable('trader_profiles')) {
                                    \Log::warning('Trader profiles table does not exist');
                                    abort(404, 'Trader profile not found');
                                }
                                
                                $trader = \Addons\TradingManagement\Modules\Marketplace\Models\TraderProfile::with(['user', 'ratings'])
                                    ->where('user_id', $id)
                                    ->public()
                                    ->firstOrFail();
                                
                                // Check if user is following this trader
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
                                
                                // Check if table exists
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
                                
                                // Check if table exists
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
                        
                        // Smart Risk Settings Update
                        Route::post('/settings/update', function (Request $request) {
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
                                
                                return back()->with('success', __('Smart Risk settings updated successfully.'));
                            } catch (\Exception $e) {
                                \Log::error('SRM settings update error: ' . $e->getMessage());
                                return back()->with('error', __('Failed to update settings. Please try again.'));
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
                                return back()->with('error', __('Failed to load create form.'));
                            }
                        })->name('create');
                        
                        Route::get('/{exchangeConnection}', function ($id) {
                            try {
                                $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('id', $id)
                                    ->where('user_id', auth()->id())
                                    ->where('is_admin_owned', false)
                                    ->firstOrFail();
                                
                                $title = 'Exchange Connection - ' . $connection->name;
                                
                                // Try to use addon view, fallback to redirect to operations page
                                if (view()->exists('trading-management::user.exchange-connections.show')) {
                                    return view('trading-management::user.exchange-connections.show', compact('title', 'connection'));
                                } else {
                                    // Fallback: redirect to operations page with connection info
                                    return redirect()->route('user.trading.operations.index', ['tab' => 'connections'])
                                        ->with('info', __('Connection details: ') . $connection->name);
                                }
                            } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
                                return redirect()->route('user.trading.operations.index', ['tab' => 'connections'])
                                    ->with('error', __('Connection not found or you do not have permission to view it.'));
                            } catch (\Exception $e) {
                                \Log::error('Exchange connection show error: ' . $e->getMessage());
                                return redirect()->route('user.trading.operations.index', ['tab' => 'connections'])
                                    ->with('error', __('Failed to load connection details.'));
                            }
                        })->name('show');
                        
                        Route::post('/', function (Request $request) {
                            try {
                                $validated = $request->validate([
                                    'name' => 'required|string|max:255',
                                    'connection_type' => 'required|in:DATA_ONLY,EXECUTION_ONLY,BOTH',
                                    'exchange_type' => 'required|in:CRYPTO_EXCHANGE,FX_BROKER',
                                    'exchange_name' => 'required|string',
                                    'credentials' => 'required|array',
                                    'preset_id' => 'nullable|exists:trading_presets,id',
                                ]);
                                
                                // Encrypt credentials
                                $encryptedCredentials = encrypt(json_encode($validated['credentials']));
                                
                                $connection = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::create([
                                    'user_id' => auth()->id(),
                                    'is_admin_owned' => false,
                                    'name' => $validated['name'],
                                    'connection_type' => $validated['connection_type'],
                                    'exchange_type' => $validated['exchange_type'],
                                    'provider' => $validated['exchange_name'], // Use exchange_name as provider
                                    'exchange_name' => $validated['exchange_name'],
                                    'credentials' => $encryptedCredentials,
                                    'preset_id' => $validated['preset_id'] ?? null,
                                    'is_active' => false,
                                    'status' => 'PENDING_TEST',
                                    'data_fetching_enabled' => $validated['connection_type'] === 'DATA_ONLY' || $validated['connection_type'] === 'BOTH',
                                    'trade_execution_enabled' => $validated['connection_type'] === 'EXECUTION_ONLY' || $validated['connection_type'] === 'BOTH',
                                ]);
                                
                                return redirect()->route('user.trading.configuration.index', ['tab' => 'data-connections'])
                                    ->with('success', __('Data connection created successfully.'));
                            } catch (\Illuminate\Validation\ValidationException $e) {
                                return back()->withErrors($e->errors())->withInput();
                            } catch (\Exception $e) {
                                \Log::error('Exchange connection store error: ' . $e->getMessage(), [
                                    'trace' => $e->getTraceAsString()
                                ]);
                                return back()->with('error', __('Failed to create connection. Please try again.'))->withInput();
                            }
                        })->name('store');
                    });
                }
            }

            Route::get('profile/setting', [UserController::class, 'profile'])->name('profile');
            Route::post('profile/setting', [UserController::class, 'profileUpdate'])->name('profileupdate');
            Route::get('profile/change/password', [UserController::class, 'changePassword'])->name('change.password');
            Route::post('profile/change/password', [UserController::class, 'updatePassword'])->name('update.password');

            // signal

            Route::get('all-signals', [SignalController::class, 'allSignals'])->name('signal.all');
            Route::get('signal-details/{id}/{slug}', [SignalController::class, 'details'])->name('signal.details');

            // plans

            Route::get('plans', [PlanController::class, 'plans'])->name('plans');
            Route::post('plans', [PlanController::class, 'subscribe'])->name('plans.post');


            // trade

            Route::get('trade', [CryptoTradeController::class, 'index'])->name('trade');
            Route::post('trade', [CryptoTradeController::class, 'openTrade']);


            Route::get('trades', [CryptoTradeController::class, 'trades'])->name('trades');

            Route::get('trade-close', [CryptoTradeController::class, 'tradeClose'])->name('tradeClose');




            Route::get('withdraw', [PayoutController::class, 'withdraw'])->name('withdraw');
            Route::get('withdraw/all', [LogController::class, 'allWithdraw'])->name('withdraw.all');
            Route::get('withdraw/pending', [LogController::class, 'pendingWithdraw'])->name('withdraw.pending');
            Route::get('withdraw/complete', [LogController::class, 'completeWithdraw'])->name('withdraw.complete');
            Route::post('withdraw', [PayoutController::class, 'withdrawCompleted']);
            Route::get('withdraw/fetch/{id}', [PayoutController::class, 'withdrawFetch'])->name('withdraw.fetch');
            Route::get('return/interest', [PayoutController::class, 'returnInterest'])->name('returninterest');


            Route::resource('ticket', TicketController::class);
            Route::post('ticket/reply', [TicketController::class, 'reply'])->name('ticket.reply');
            Route::get('ticket/reply/status/change/{id}', [TicketController::class, 'statusChange'])->name('ticket.status-change');

            Route::get('ticket/status/{status}', [TicketController::class, 'ticketStatus'])->name('ticket.status');

            Route::get('ticket/attachement/{id}', [TicketController::class, 'ticketDownload'])->name('ticket.download');

            

            Route::get('gateways/{id}', [PaymentController::class, 'gateways'])->name('gateways');

            Route::post('paynow/{id}', [PaymentController::class, 'paynow'])->name('paynow');

            Route::get('gateways/{id}/details', [PaymentController::class, 'gatewaysDetails'])->name('gateway.details');

            Route::post('gateways/{id}/details', [PaymentController::class, 'gatewayRedirect']);

            Route::any('payment-success/{gateway}', [PaymentController::class, 'paymentSuccess'])->name('payment.success');

            Route::match(['get', 'post'], '/payments/crypto/pay', Victorybiz\LaravelCryptoPaymentGateway\Http\Controllers\CryptoPaymentController::class)
                ->name('payments.crypto.pay');
            
            Route::post('/payments/crypto/callback', [\App\Services\Gateway\Gourl::class, 'callback'])->withoutMiddleware(['web', 'auth'])->name('payments.crypto.callback');


            Route::get('transfer-money', [MoneyTransferController::class, 'transfer'])->name('transfer_money');
            Route::post('transfer-money', [MoneyTransferController::class, 'transferMoney']);
            Route::get('transfer-money/log', [MoneyTransferController::class, 'transferMoneyLog'])->name('transfer_money.log');
            Route::get('receiver-money/log', [MoneyTransferController::class, 'receiveMoneyLog'])->name('receive_money.log');



            Route::get('invest/all', [UserController::class, 'allInvest'])->name('invest.all');
            Route::get('invest/pending', [UserController::class, 'pendingInvest'])->name('invest.pending');
            Route::get('invest/log', [LogController::class, 'investLog'])->name('invest.log');

            // logs
            Route::get('transaction/log', [LogController::class, 'transactionLog'])->name('transaction.log');

            Route::get('interest/log', [UserController::class, 'interestLog'])->name('interest.log');


            Route::get('deposit', [DepositController::class, 'deposit'])->name('deposit');

            Route::get('deposit/log', [LogController::class, 'depositLog'])->name('deposit.log');

            Route::get('commision', [LogController::class, 'Commision'])->name('commision');

            Route::get('subscription-log', [LogController::class, 'subscriptionLog'])->name('subscription');

            Route::get('refferal', [LogController::class, 'refferalLog'])->name('refferalLog');
            }); // End check_onboarding middleware group
        });
    });
});

Route::get('/', [FrontendController::class, 'index'])->name('home');

Route::get('/swagger', function () {
    return view('swagger');
})->name('swagger');

Route::get('/docs', function () {
    return view('swagger');
})->name('docs');

Route::get('/docs.openapi', function () {
    $path = storage_path('app/scribe/openapi.yaml');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Content-Type' => 'application/yaml'
    ]);
})->name('scribe.openapi');

Route::get('/docs.postman', function () {
    $path = storage_path('app/scribe/collection.json');
    if (!file_exists($path)) {
        abort(404);
    }
    return response()->file($path, [
        'Content-Type' => 'application/json'
    ]);
})->name('scribe.postman');

Route::get('trading-return', [CryptoTradeController::class, 'tradingInterest'])->name('trading-interest');

Route::get('change-language', [FrontendController::class, 'changeLanguage'])->name('change-language');

// API routes - must be before catch-all route
Route::middleware(['web', 'auth'])->group(function () {
    Route::get('current-price', [CryptoTradeController::class, 'currentPrice'])->name('user.current-price');
    Route::get('get-ticker', [CryptoTradeController::class, 'latestTicker'])->name('ticker');
    Route::get('stream-prices', [CryptoTradeController::class, 'streamPrices'])->name('stream.prices');
});

Route::get('blog/{id}/{slug}', [FrontendController::class, 'blogDetails'])->name('blog.details');

Route::get('links/{id}/{slug}', [FrontendController::class, 'linksDetails'])->name('links');

Route::post('subscribe', [FrontendController::class, 'subscribe'])->name('subscribe');

Route::post('contact', [FrontendController::class, 'contactSend'])->name('contact');

// Catch-all route must be last
Route::get('{pages}', [FrontendController::class, 'page'])->name('pages');
