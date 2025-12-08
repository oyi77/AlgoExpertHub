<?php

namespace App\Http\Controllers\User\Trading;

use App\Helpers\Helper\Helper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplacesController extends Controller
{
    /**
     * Display unified Marketplaces page with categories
     */
    public function index(Request $request)
    {
        $data['title'] = __('Marketplaces');
        $data['activeCategory'] = $request->get('category', 'trading-presets');
        
        // Check if addon is enabled
        $data['tradingManagementEnabled'] = \App\Support\AddonRegistry::active('trading-management-addon');

        if ($data['tradingManagementEnabled']) {
            // Trading Presets category
            if ($data['activeCategory'] === 'trading-presets') {
                if (class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
                    $data['items'] = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::whereNull('created_by_user_id')
                        ->where('visibility', 'PUBLIC_MARKETPLACE')
                        ->latest()
                        ->paginate(20, ['*'], 'presets_page');
                }
            }

            // Filter Strategies category
            if ($data['activeCategory'] === 'filter-strategies') {
                if (class_exists(\Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::class)) {
                    $data['items'] = \Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy::whereNull('created_by_user_id')
                        ->where('visibility', 'PUBLIC_MARKETPLACE')
                        ->latest()
                        ->paginate(20, ['*'], 'strategies_page');
                }
            }

            // AI Model Profiles category
            if ($data['activeCategory'] === 'ai-profiles') {
                if (class_exists(\Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::class)) {
                    $data['items'] = \Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile::whereNull('created_by_user_id')
                        ->where('visibility', 'PUBLIC_MARKETPLACE')
                        ->latest()
                        ->paginate(20, ['*'], 'ai_profiles_page');
                }
            }

            // Copy Trading category
            if ($data['activeCategory'] === 'copy-trading') {
                // Load copy trading traders/subscriptions
                $data['items'] = collect([]); // Placeholder - implement when copy trading module is ready
            }

            // Bot Marketplace category
            if ($data['activeCategory'] === 'bot-marketplace') {
                if (class_exists(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class)) {
                    $data['items'] = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::whereNull('user_id')
                        ->where('visibility', 'PUBLIC_MARKETPLACE')
                        ->with(['exchangeConnection', 'tradingPreset'])
                        ->latest()
                        ->paginate(20, ['*'], 'bots_page');
                }
            }
        }

        return view(Helper::theme() . 'user.trading.marketplaces', $data);
    }
}
