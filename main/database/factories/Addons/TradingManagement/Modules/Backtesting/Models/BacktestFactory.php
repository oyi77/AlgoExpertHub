<?php

namespace Database\Factories\Addons\TradingManagement\Modules\Backtesting\Models;

use Addons\TradingManagement\Modules\Backtesting\Models\Backtest;
use App\Models\User;
use App\Models\Admin;
use Addons\TradingManagement\Modules\RiskManagement\Models\TradingPreset;
use Addons\TradingManagement\Modules\FilterStrategy\Models\FilterStrategy;
use Addons\TradingManagement\Modules\AiAnalysis\Models\AiModelProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

class BacktestFactory extends Factory
{
    protected $model = Backtest::class;

    public function definition()
    {
        return [
            'user_id' => User::factory(),
            'admin_id' => null,
            'name' => $this->faker->words(3, true) . ' Strategy',
            'description' => $this->faker->sentence(),
            'symbol' => $this->faker->randomElement(['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'ADAUSDT']),
            'timeframe' => $this->faker->randomElement(['5m', '15m', '30m', '1h', '4h', '1d']),
            'start_date' => now()->subMonths(6),
            'end_date' => now()->subMonth(),
            'initial_balance' => $this->faker->randomFloat(2, 1000, 50000),
            'preset_id' => TradingPreset::factory(),
            'filter_strategy_id' => null,
            'ai_model_profile_id' => null,
            'status' => 'pending',
            'progress_percent' => 0,
            'error_message' => null,
        ];
    }

    public function completed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'completed',
                'progress_percent' => 100,
            ];
        });
    }

    public function running()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'running',
                'progress_percent' => $this->faker->numberBetween(10, 90),
            ];
        });
    }

    public function failed()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 'failed',
                'progress_percent' => $this->faker->numberBetween(0, 50),
                'error_message' => 'Insufficient historical data',
            ];
        });
    }

    public function withFilterStrategy()
    {
        return $this->state(function (array $attributes) {
            return [
                'filter_strategy_id' => FilterStrategy::factory(),
            ];
        });
    }

    public function withAiProfile()
    {
        return $this->state(function (array $attributes) {
            return [
                'ai_model_profile_id' => AiModelProfile::factory(),
            ];
        });
    }
}
