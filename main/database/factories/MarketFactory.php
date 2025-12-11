<?php

namespace Database\Factories;

use App\Models\Market;
use Illuminate\Database\Eloquent\Factories\Factory;

class MarketFactory extends Factory
{
    protected $model = Market::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['Forex', 'Crypto', 'Stocks', 'Commodities', 'Indices']),
            'status' => 1,
        ];
    }
}