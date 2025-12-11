<?php

namespace App\Http\Controllers\Api\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * @group Marketplace
 *
 * Endpoints for bot templates, trader profiles, and marketplace features.
 */
class MarketplaceApiController extends Controller
{
    /**
     * List Bot Templates
     */
    public function botTemplates()
    {
        if (!class_exists(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class)) {
            return response()->json(['success' => false, 'message' => 'Trading management addon not available'], 503);
        }

        $templates = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::where('is_template', true)
            ->where('visibility', 'PUBLIC_MARKETPLACE')
            ->with(['creator'])
            ->get();

        return response()->json(['success' => true, 'data' => $templates]);
    }

    /**
     * Clone Bot Template
     */
    public function cloneTemplate(Request $request, $id)
    {
        if (!class_exists(\Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::class)) {
            return response()->json(['success' => false, 'message' => 'Trading management addon not available'], 503);
        }

        $template = \Addons\TradingManagement\Modules\TradingBot\Models\TradingBot::where('is_template', true)
            ->where('visibility', 'PUBLIC_MARKETPLACE')
            ->findOrFail($id);

        $clone = $template->replicate();
        $clone->user_id = Auth::id();
        $clone->is_template = false;
        $clone->visibility = 'PRIVATE';
        $clone->name = $template->name . ' (Copy)';
        $clone->save();

        return response()->json(['success' => true, 'data' => $clone], 201);
    }

    /**
     * List Trading Presets
     */
    public function tradingPresets()
    {
        if (!class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
            return response()->json(['success' => false, 'message' => 'Risk management module not available'], 503);
        }

        $presets = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where('visibility', 'PUBLIC_MARKETPLACE')
            ->get();

        return response()->json(['success' => true, 'data' => $presets]);
    }

    /**
     * Clone Trading Preset
     */
    public function clonePreset($id)
    {
        if (!class_exists(\Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::class)) {
            return response()->json(['success' => false, 'message' => 'Risk management module not available'], 503);
        }

        $preset = \Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset::where('visibility', 'PUBLIC_MARKETPLACE')
            ->findOrFail($id);

        $clone = $preset->replicate();
        $clone->created_by_user_id = Auth::id();
        $clone->visibility = 'PRIVATE';
        $clone->name = $preset->name . ' (Copy)';
        $clone->save();

        return response()->json(['success' => true, 'data' => $clone], 201);
    }
}
