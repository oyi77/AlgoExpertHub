<?php

namespace Database\Factories;

use App\Models\Signal;
use App\Models\Market;
use App\Models\CurrencyPair;
use App\Models\TimeFrame;
use Illuminate\Database\Eloquent\Factories\Factory;

class SignalFactory extends Factory
{
    protected $model = Signal::class;

    public function definition()
    {
        $direction = $this->faker->randomElement(['buy', 'sell', 'long', 'short']);
        $openPrice = $this->faker->randomFloat(5, 1, 100);
        
        // Calculate realistic SL and TP based on direction
        if (in_array($direction, ['buy', 'long'])) {
            $sl = $openPrice * $this->faker->randomFloat(3, 0.95, 0.99); // SL below entry
            $tp = $openPrice * $this->faker->randomFloat(3, 1.01, 1.10); // TP above entry
        } else {
            $sl = $openPrice * $this->faker->randomFloat(3, 1.01, 1.05); // SL above entry
            $tp = $openPrice * $this->faker->randomFloat(3, 0.90, 0.99); // TP below entry
        }

        return [
            'title' => $this->faker->sentence(3),
            'currency_pair_id' => CurrencyPair::factory(),
            'time_frame_id' => TimeFrame::factory(),
            'market_id' => Market::factory(),
            'open_price' => $openPrice,
            'sl' => $sl,
            'tp' => $tp,
            'direction' => $direction,
            'is_published' => $this->faker->boolean(70), // 70% chance of being published
            'published_date' => $this->faker->optional(0.7)->dateTimeBetween('-1 month', 'now'),
            'description' => $this->faker->optional()->paragraph(),
            'image' => null,
            'auto_created' => $this->faker->boolean(30), // 30% chance of being auto-created
            'status' => 1,
        ];
    }

    public function published()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => 1,
                'published_date' => now(),
            ];
        });
    }

    public function autoCreated()
    {
        return $this->state(function (array $attributes) {
            return [
                'auto_created' => 1,
            ];
        });
    }

    public function draft()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_published' => 0,
                'published_date' => null,
            ];
        });
    }

    public function buySignal()
    {
        return $this->state(function (array $attributes) {
            $openPrice = $this->faker->randomFloat(5, 1, 100);
            return [
                'direction' => 'buy',
                'open_price' => $openPrice,
                'sl' => $openPrice * 0.97, // 3% below entry
                'tp' => $openPrice * 1.05, // 5% above entry
            ];
        });
    }

    public function sellSignal()
    {
        return $this->state(function (array $attributes) {
            $openPrice = $this->faker->randomFloat(5, 1, 100);
            return [
                'direction' => 'sell',
                'open_price' => $openPrice,
                'sl' => $openPrice * 1.03, // 3% above entry
                'tp' => $openPrice * 0.95, // 5% below entry
            ];
        });
    }

    public function forexSignal()
    {
        return $this->state(function (array $attributes) {
            return [
                'market_id' => Market::factory()->forex(),
                'currency_pair_id' => CurrencyPair::factory()->forex(),
            ];
        });
    }

    public function cryptoSignal()
    {
        return $this->state(function (array $attributes) {
            return [
                'market_id' => Market::factory()->crypto(),
                'currency_pair_id' => CurrencyPair::factory()->crypto(),
            ];
        });
    }
}