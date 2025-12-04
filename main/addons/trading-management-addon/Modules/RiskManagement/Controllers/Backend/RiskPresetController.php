<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend;

use App\Http\Controllers\Controller;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Http\Request;

class RiskPresetController extends Controller
{
    public function index()
    {
        $title = 'Risk Presets';
        $presets = TradingPreset::orderBy('created_at', 'desc')->paginate(20);
        return view('trading-management::backend.trading-management.config.risk-presets.index', compact('title', 'presets'));
    }

    public function create()
    {
        $title = 'Create Risk Preset';
        return view('trading-management::backend.trading-management.config.risk-presets.create', compact('title'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position_size_mode' => 'required|in:FIXED,RISK_PERCENT',
            'risk_per_trade_pct' => 'nullable|numeric|min:0|max:100',
            'fixed_lot' => 'nullable|numeric|min:0',
        ]);

        TradingPreset::create([
            ...$validated,
            'enabled' => true,
            'is_default_template' => $request->boolean('is_default_template'),
        ]);

        return redirect()->route('admin.trading-management.config.risk-presets.index')
            ->with('success', 'Risk preset created successfully');
    }

    public function edit(TradingPreset $riskPreset)
    {
        $title = 'Edit Risk Preset';
        $preset = $riskPreset;
        return view('trading-management::backend.trading-management.config.risk-presets.edit', compact('title', 'preset'));
    }

    public function update(Request $request, TradingPreset $riskPreset)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'position_size_mode' => 'required|in:FIXED,RISK_PERCENT',
            'risk_per_trade_pct' => 'nullable|numeric|min:0|max:100',
            'fixed_lot' => 'nullable|numeric|min:0',
            'enabled' => 'sometimes|boolean',
        ]);

        $riskPreset->update([
            ...$validated,
            'is_default_template' => $request->boolean('is_default_template'),
        ]);

        return redirect()->route('admin.trading-management.config.risk-presets.index')
            ->with('success', 'Risk preset updated successfully');
    }

    public function destroy(TradingPreset $riskPreset)
    {
        $riskPreset->delete();
        return redirect()->route('admin.trading-management.config.risk-presets.index')
            ->with('success', 'Risk preset deleted successfully');
    }
}

