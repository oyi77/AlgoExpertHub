<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradingConfigurationsController extends Controller
{
    /**
     * Display unified Trading Configurations page with tabs
     */
    public function index(Request $request)
    {
        $data['title'] = __('Trading Configurations');
        $data['activeTab'] = $request->get('tab', 'risk-presets');
        
        // Check if addon is enabled
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon');

        if ($data['tradingManagementEnabled']) {
            // Risk Presets tab
            if ($data['activeTab'] === 'risk-presets') {
                try {
                    if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
                        $data['presets'] = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where(function($query) {
                                $query->where('created_by_user_id', Auth::id())
                                      ->orWhereNull('created_by_user_id'); // Allow system presets
                            })
                            ->latest()
                            ->paginate(20, ['*'], 'presets_page');
                    } else {
                        $data['presets'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                } catch (\Exception $e) {
                    \Log::error('TradingConfigurations: Error loading risk presets', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $data['presets'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                }
            }

            // Smart Risk Management tab
            if ($data['activeTab'] === 'smart-risk') {
                // Load smart risk settings (per user)
                $data['smartRiskSettings'] = \Illuminate\Support\Facades\Cache::get('smart_risk_settings_' . Auth::id(), [
                    'enabled' => false,
                    'min_provider_score' => 70,
                    'slippage_buffer_enabled' => false,
                    'dynamic_lot_enabled' => false,
                ]);
            }

            // Filter Strategies tab
            if ($data['activeTab'] === 'filter-strategies') {
                try {
                    if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
                        $data['filterStrategies'] = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::where('created_by_user_id', Auth::id())
                            ->latest()
                            ->paginate(20, ['*'], 'filter_strategies_page');
                    } else {
                        $data['filterStrategies'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                } catch (\Exception $e) {
                    \Log::error('TradingConfigurations: Error loading filter strategies', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $data['filterStrategies'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                }
            }

            // AI Model Profiles tab
            if ($data['activeTab'] === 'ai-profiles') {
                try {
                    if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
                        $data['aiProfiles'] = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::where('created_by_user_id', Auth::id())
                            ->latest()
                            ->paginate(20, ['*'], 'ai_profiles_page');
                    } else {
                        $data['aiProfiles'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                    }
                } catch (\Exception $e) {
                    \Log::error('TradingConfigurations: Error loading AI model profiles', [
                        'error' => $e->getMessage(),
                        'trace' => $e->getTraceAsString()
                    ]);
                    $data['aiProfiles'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                }
            }
        }

        return view(Helper::themeView('user.trading.configurations', $data);
    }
}

