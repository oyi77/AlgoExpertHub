<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BacktestingController extends Controller
{
    /**
     * Display unified Backtesting page with tabs
     */
    public function index(Request $request)
    {
        $data['title'] = __('Backtesting');
        $data['activeTab'] = $request->get('tab', 'create');
        
        // Check if addon is enabled
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon')
            && \App\Support\AddonRegistry::moduleEnabled('trading-management-addon', 'backtesting');

        if ($data['tradingManagementEnabled']) {
            // Create Backtest tab
            if ($data['activeTab'] === 'create') {
                // Load available presets, strategies, etc. for backtest creation
                if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
                    $data['presets'] = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where(function($query) {
                            $query->where('created_by_user_id', Auth::id())
                                  ->orWhereNull('created_by_user_id');
                        })
                        ->get();
                }
            }

            // Results tab
            if ($data['activeTab'] === 'results') {
                // Load backtest results (if backtesting module exists)
                $data['results'] = collect([]); // Placeholder
            }

            // Performance Reports tab
            if ($data['activeTab'] === 'reports') {
                // Load performance reports
                $data['reports'] = collect([]); // Placeholder
            }
        }

        return view(Helper::theme() . 'user.trading.backtesting', $data);
    }
}
