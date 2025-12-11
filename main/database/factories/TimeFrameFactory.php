<?php

namespace Database\Factories;

use App\Models\TimeFrame;
use Illuminate\Database\Eloquent\Factories\Factory;

class TimeFrameFactory extends Factory
{
    protected $model = TimeFrame::class;

    public function definition()
    {
        return [
            'name' => $this->faker->randomElement(['1M', '5M', '15M', '30M', '1H', '4H', '1D', '1W', '1MO']),
            'status' => 1,
        ];
    }
}