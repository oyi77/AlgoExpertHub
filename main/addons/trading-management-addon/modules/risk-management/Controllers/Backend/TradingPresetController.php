<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Http\Request;

class TradingPresetController extends Controller
{
    public function index()
    {
        $presets = TradingPreset::with('creator', 'filterStrategy', 'aiModelProfile')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('trading-management::backend.trading-management.config.risk-presets.index', compact('presets'));
    }

    public function create()
    {
        $filterStrategies = FilterStrategy::enabled()->get();
        $aiModelProfiles = AiModelProfile::enabled()->get();
        
        return view('trading-management::backend.trading-management.config.risk-presets.create', compact('filterStrategies', 'aiModelProfiles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position_size_mode' => 'required|in:FIXED,RISK_PERCENT',
            'fixed_lot' => 'nullable|numeric|min:0.01',
            'risk_per_trade_pct' => 'nullable|numeric|min:0.1|max:10',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'smart_risk_enabled' => 'boolean',
        ]);

        $validated['created_by_user_id'] = null; // Admin-created
        $validated['is_default_template'] = $request->has('is_default_template');

        TradingPreset::create($validated);

        return redirect()->route('admin.trading-management.config.risk-presets.index')
            ->with('success', 'Trading preset created successfully');
    }

    public function edit(TradingPreset $tradingPreset)
    {
        $filterStrategies = FilterStrategy::enabled()->get();
        $aiModelProfiles = AiModelProfile::enabled()->get();
        
        return view('trading-management::backend.trading-management.config.risk-presets.edit', [
            'preset' => $tradingPreset,
            'filterStrategies' => $filterStrategies,
            'aiModelProfiles' => $aiModelProfiles,
        ]);
    }

    public function update(Request $request, TradingPreset $tradingPreset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position_size_mode' => 'required|in:FIXED,RISK_PERCENT',
            'fixed_lot' => 'nullable|numeric',
            'risk_per_trade_pct' => 'nullable|numeric',
            'filter_strategy_id' => 'nullable|exists:filter_strategies,id',
            'ai_model_profile_id' => 'nullable|exists:ai_model_profiles,id',
            'smart_risk_enabled' => 'boolean',
        ]);

        $tradingPreset->update($validated);

        return redirect()->route('admin.trading-management.config.risk-presets.index')
            ->with('success', 'Trading preset updated successfully');
    }

    public function destroy(TradingPreset $tradingPreset)
    {
        if ($tradingPreset->isDefaultTemplate()) {
            return redirect()->back()->with('error', 'Cannot delete default template');
        }

        $tradingPreset->delete();

        return redirect()->route('admin.trading-management.config.risk-presets.index')
            ->with('success', 'Trading preset deleted successfully');
    }
}

