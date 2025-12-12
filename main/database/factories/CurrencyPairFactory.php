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

    public function forex()
    {
        return $this->state(function (array $attributes) {
            $forexPairs = ['EUR/USD', 'GBP/USD', 'USD/JPY', 'USD/CHF', 'AUD/USD', 'USD/CAD'];
            return [
                'name' => $this->faker->randomElement($forexPairs),
            ];
        });
    }

    public function crypto()
    {
        return $this->state(function (array $attributes) {
            $cryptoPairs = ['BTC/USDT', 'ETH/USDT', 'BNB/USDT', 'ADA/USDT', 'DOT/USDT'];
            return [
                'name' => $this->faker->randomElement($cryptoPairs),
            ];
        });
    }

    public function stocks()
    {
        return $this->state(function (array $attributes) {
            $stockPairs = ['AAPL', 'GOOGL', 'MSFT', 'TSLA', 'AMZN', 'NVDA'];
            return [
                'name' => $this->faker->randomElement($stockPairs),
            ];
        });
    }

    public function commodities()
    {
        return $this->state(function (array $attributes) {
            $commodityPairs = ['GOLD', 'SILVER', 'OIL', 'COPPER'];
            return [
                'name' => $this->faker->randomElement($commodityPairs),
            ];
        });
    }
}