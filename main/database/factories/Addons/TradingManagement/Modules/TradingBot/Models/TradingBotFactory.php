<?php

namespace Database\Factories\Addons\TradingManagement\Modules\TradingBot\Models;

use Addons\TradingManagement\Modules\TradingBot\Models\TradingBot;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Illuminate\Database\Eloquent\Factories\Factory;

class TradingBotFactory extends Factory
{
    protected $model = TradingBot::class;

    public function definition()
    {
        return [
            'name' => $this->faker->name . ' Bot',
            'status' => 'stopped',
            'is_active' => true,
            'is_paper_trading' => true,
            'trading_mode' => 'SIGNAL_BASED',
            'trading_preset_id' => TradingPreset::factory(),
        ];
    }
}
