<?php

declare(strict_types=1);

namespace Database\Factories\Addons\TradingManagement\Modules\Execution\Models;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class ExecutionConnectionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'user_id' => null,
            'admin_id' => null,
            'name' => $this->faker->company() . ' Connection',
            'type' => $this->faker->randomElement(['crypto', 'forex', 'stock']),
            'exchange_name' => $this->faker->randomElement(['binance', 'coinbase', 'metatrader', 'oanda', 'ibkr']),
            'credentials' => encrypt(json_encode([
                'api_key' => $this->faker->uuid(),
                'api_secret' => $this->faker->uuid(),
            ])),
            'status' => 'active',
            'is_active' => true,
            'is_admin_owned' => false,
            'last_error' => null,
            'last_tested_at' => now(),
            'last_used_at' => now(),
            'settings' => [
                'test_mode' => true,
                'timeout' => 30,
            ],
            'preset_id' => null,
            'data_connection_id' => null,
            'leverage' => 1,
            'margin_call_threshold' => 0.5,
            'liquidation_threshold' => 0.2,
            'max_margin_usage_pct' => 0.8,
            'max_open_positions' => 5,
            'max_positions_per_symbol' => 2,
            'circuit_breaker_enabled' => false,
            'max_consecutive_failures' => 5,
            'consecutive_failures' => 0,
            'last_failure_at' => null,
        ];
    }

    public function forUser($userId)
    {
        return $this->state(function (array $attributes) use ($userId) {
            return [
                'user_id' => $userId,
                'is_admin_owned' => false,
            ];
        });
    }

    public function crypto()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'crypto',
                'exchange_name' => $this->faker->randomElement(['binance', 'coinbase', 'kraken']),
            ];
        });
    }

    public function forex()
    {
        return $this->state(function (array $attributes) {
            return [
                'type' => 'forex',
                'exchange_name' => $this->faker->randomElement(['metatrader', 'oanda', 'ibkr']),
            ];
        });
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_active' => true,
                'status' => 'active',
            ];
        });
    }
}
