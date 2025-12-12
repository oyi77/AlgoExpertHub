<?php

namespace Database\Factories;

use App\Models\PlanSubscription;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

class PlanSubscriptionFactory extends Factory
{
    protected $model = PlanSubscription::class;

    public function definition()
    {
        $startDate = $this->faker->dateTimeBetween('-1 month', 'now');
        $endDate = $this->faker->dateTimeBetween($startDate, '+1 year');

        return [
            'user_id' => User::factory(),
            'plan_id' => Plan::factory(),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'is_current' => $this->faker->boolean(50),
            'status' => $this->faker->randomElement(['active', 'expired', 'cancelled']),
        ];
    }

    public function active()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_current' => 1,
                'status' => 'active',
                'end_date' => $this->faker->dateTimeBetween('now', '+1 year'),
            ];
        });
    }

    public function expired()
    {
        return $this->state(function (array $attributes) {
            return [
                'is_current' => 0,
                'status' => 'expired',
                'end_date' => $this->faker->dateTimeBetween('-1 year', 'now'),
            ];
        });
    }
}