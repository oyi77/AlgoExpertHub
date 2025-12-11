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

    public function forex()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Forex',
            ];
        });
    }

    public function crypto()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Crypto',
            ];
        });
    }

    public function stocks()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Stocks',
            ];
        });
    }

    public function commodities()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Commodities',
            ];
        });
    }
}