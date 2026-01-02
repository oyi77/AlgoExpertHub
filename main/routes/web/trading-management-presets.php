<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use App\Helpers\NotificationHelper;

/*
|--------------------------------------------------------------------------
| Trading Management - Presets, Filter Strategies, AI Profiles
|--------------------------------------------------------------------------
*/

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
