<?php

namespace Addons\TradingManagement\Modules\RiskManagement\Controllers\Backend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class SmartRiskController extends Controller
{
    public function index()
    {
        $title = 'Smart Risk Settings';
        
        // Get current settings from cache or defaults
        $settings = Cache::get('smart_risk_settings', [
            'enabled' => false,
            'min_provider_score' => 70,
            'slippage_buffer_enabled' => false,
            'dynamic_lot_enabled' => false,
            'max_risk_multiplier' => 2.0,
            'min_risk_multiplier' => 0.5,
        ]);
        
        return view('trading-management::backend.trading-management.config.smart-risk.index', compact('title', 'settings'));
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'enabled' => 'required|boolean',
            'min_provider_score' => 'required|numeric|min:0|max:100',
            'slippage_buffer_enabled' => 'required|boolean',
            'dynamic_lot_enabled' => 'required|boolean',
            'max_risk_multiplier' => 'required|numeric|min:1|max:5',
            'min_risk_multiplier' => 'required|numeric|min:0.1|max:1',
        ]);

        Cache::put('smart_risk_settings', $validated, now()->addYear());

        return redirect()->route('admin.trading-management.config.smart-risk.index')
            ->with('success', 'Smart Risk settings updated successfully');
    }
}

