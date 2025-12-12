<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TradingConfigurationController extends Controller
{
    /**
     * Display unified Trading Configuration page with tabs
     */
    public function index(Request $request)
    {
        $data['title'] = __('Trading Configuration');
        $data['activeTab'] = $request->get('tab', 'data-connections');
        
        // Check if addon is enabled
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon');

        if ($data['tradingManagementEnabled']) {
            try {
                // Data Connections tab
                if ($data['activeTab'] === 'data-connections') {
                    if (class_exists(\Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::class)) {
                        try {
                            $data['dataConnections'] = \Addons\TradingManagement\Modules\ExchangeConnection\Models\ExchangeConnection::where('user_id', Auth::id())
                                ->where('is_admin_owned', false)
                                ->where('connection_type', 'DATA_ONLY')
                                ->with(['user', 'preset'])
                                ->latest()
                                ->paginate(20, ['*'], 'data_connections_page');
                        } catch (\Exception $e) {
                            \Log::error('TradingConfiguration: Error loading data connections', ['error' => $e->getMessage()]);
                            $data['dataConnections'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }

                // Risk Presets tab
                if ($data['activeTab'] === 'risk-presets') {
                    try {
                        if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
                            $data['presets'] = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where(function($query) {
                                    $query->where('created_by_user_id', Auth::id())
                                          ->orWhereNull('created_by_user_id');
                                })
                                ->latest()
                                ->paginate(20, ['*'], 'presets_page');
                        } else {
                            $data['presets'] = collect([])->paginate(20, ['*'], 'presets_page');
                        }
                    } catch (\Exception $e) {
                        \Log::error('TradingConfiguration: Error loading risk presets', ['error' => $e->getMessage()]);
                        $data['presets'] = collect([])->paginate(20, ['*'], 'presets_page');
                    }
                }

                // Smart Risk Management tab
                if ($data['activeTab'] === 'smart-risk') {
                    try {
                        // Load smart risk settings (per user)
                        $data['smartRiskSettings'] = \Illuminate\Support\Facades\Cache::get('smart_risk_settings_' . Auth::id(), [
                            'enabled' => false,
                            'min_provider_score' => 70,
                            'slippage_buffer_enabled' => false,
                            'dynamic_lot_enabled' => false,
                        ]);
                    } catch (\Exception $e) {
                        \Log::error('TradingConfiguration: Error loading smart risk settings', ['error' => $e->getMessage()]);
                        $data['smartRiskSettings'] = [
                            'enabled' => false,
                            'min_provider_score' => 70,
                            'slippage_buffer_enabled' => false,
                            'dynamic_lot_enabled' => false,
                        ];
                    }
                }

                // Filter Strategies tab
                if ($data['activeTab'] === 'filter-strategies') {
                    if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
                        try {
                            $data['filterStrategies'] = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::where('created_by_user_id', Auth::id())
                                ->latest()
                                ->paginate(20, ['*'], 'filter_strategies_page');
                        } catch (\Exception $e) {
                            \Log::error('TradingConfiguration: Error loading filter strategies', ['error' => $e->getMessage()]);
                            $data['filterStrategies'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }

                // AI Model Profiles tab
                if ($data['activeTab'] === 'ai-profiles') {
                    if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
                        try {
                            $data['aiProfiles'] = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::where('created_by_user_id', Auth::id())
                                ->latest()
                                ->paginate(20, ['*'], 'ai_profiles_page');
                        } catch (\Exception $e) {
                            \Log::error('TradingConfiguration: Error loading AI profiles', ['error' => $e->getMessage()]);
                            $data['aiProfiles'] = new \Illuminate\Pagination\LengthAwarePaginator(collect([]), 0, 20, 1);
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::error('TradingConfiguration: General error', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            }
        }

        return view(Helper::themeView('user.trading.configuration'), $data);
    }
}
