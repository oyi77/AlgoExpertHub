<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition()
    {
        return [
            'name' => $this->faker->words(2, true) . ' Plan',
            'description' => $this->faker->paragraph(),
            'price' => $this->faker->randomFloat(2, 10, 500),
            'plan_type' => $this->faker->randomElement(['limited', 'lifetime']),
            'duration' => $this->faker->numberBetween(7, 365), // days
            'status' => 1,
            'image' => null,
        ];
    }

    public function lifetime()
    {
        return $this->state(function (array $attributes) {
            return [
                'plan_type' => 'lifetime',
                'duration' => null,
            ];
        });
    }

    public function limited()
    {
        return $this->state(function (array $attributes) {
            return [
                'plan_type' => 'limited',
                'duration' => $this->faker->numberBetween(7, 365),
            ];
        });
    }

    public function inactive()
    {
        return $this->state(function (array $attributes) {
            return [
                'status' => 0,
            ];
        });
    }

    public function premium()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Premium Plan',
                'price' => $this->faker->randomFloat(2, 100, 300),
                'plan_type' => 'limited',
                'duration' => 30,
            ];
        });
    }

    public function basic()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'Basic Plan',
                'price' => $this->faker->randomFloat(2, 10, 50),
                'plan_type' => 'limited',
                'duration' => 7,
            ];
        });
    }

    public function vip()
    {
        return $this->state(function (array $attributes) {
            return [
                'name' => 'VIP Plan',
                'price' => $this->faker->randomFloat(2, 500, 1000),
                'plan_type' => 'lifetime',
                'duration' => null,
            ];
        });
    }
}