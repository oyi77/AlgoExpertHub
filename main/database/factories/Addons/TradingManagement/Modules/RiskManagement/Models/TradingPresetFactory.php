<?php

namespace Database\Factories\Addons\TradingManagement\Modules\RiskManagement\Models;

use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradingPresetFactory extends Factory
{
    protected $model = TradingPreset::class;

    public function definition()
    {
        return [
            'name' => 'Default Preset',
            'risk_per_trade_pct' => 0.01,
            'max_positions' => 3,
            'enabled' => true,
        ];
    }
}
