<?php

namespace Database\Factories;

use App\Models\CurrencyPair;
use Illuminate\Database\Eloquent\Factories\Factory;

class CurrencyPairFactory extends Factory
{
    protected $model = CurrencyPair::class;

    public function definition()
    {
        $pairs = [
            'EUR/USD', 'GBP/USD', 'USD/JPY', 'USD/CHF', 'AUD/USD', 'USD/CAD',
            'BTC/USDT', 'ETH/USDT', 'BNB/USDT', 'ADA/USDT', 'DOT/USDT',
            'AAPL', 'GOOGL', 'MSFT', 'TSLA', 'AMZN', 'NVDA',
            'GOLD', 'SILVER', 'OIL', 'COPPER'
        ];

        return [
            'name' => $this->faker->randomElement($pairs),
            'status' => 1,
        ];
    }
}