<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            'username' => $this->faker->unique()->userName(),
            'email' => $this->faker->unique()->safeEmail(),
            'email_verified_at' => now(),
            'is_email_verified' => 1,
            'password' => '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', // password
            'first_name' => $this->faker->firstName(),
            'last_name' => $this->faker->lastName(),
            'phone' => $this->faker->optional()->phoneNumber(),
            'balance' => $this->faker->randomFloat(2, 0, 1000),
            'status' => 1,
            'kyc_status' => $this->faker->randomElement(['unverified', 'pending', 'approved']),
            'ref_id' => null,
            'telegram_chat_id' => $this->faker->optional()->numerify('##########'),
            'address' => [
                'country' => $this->faker->country(),
                'city' => $this->faker->city(),
                'state' => $this->faker->state(),
                'zip' => $this->faker->postcode(),
                'street' => $this->faker->streetAddress(),
            ],
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    public function unverified()
    {
        return $this->state(function (array $attributes) {
            return [
                'email_verified_at' => null,
                'is_email_verified' => 0,
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

    public function kycApproved()
    {
        return $this->state(function (array $attributes) {
            return [
                'kyc_status' => 'approved',
                'kyc_information' => [
                    'document_type' => 'passport',
                    'document_number' => $this->faker->bothify('??######'),
                    'approved_at' => now()->toISOString(),
                ],
            ];
        });
    }

    public function kycPending()
    {
        return $this->state(function (array $attributes) {
            return [
                'kyc_status' => 'pending',
                'kyc_information' => [
                    'document_type' => 'passport',
                    'document_number' => $this->faker->bothify('??######'),
                    'submitted_at' => now()->toISOString(),
                ],
            ];
        });
    }

    public function withReferrer()
    {
        return $this->state(function (array $attributes) {
            return [
                'ref_id' => \App\Models\User::factory(),
            ];
        });
    }

    public function withBalance($amount = null)
    {
        return $this->state(function (array $attributes) use ($amount) {
            return [
                'balance' => $amount ?? $this->faker->randomFloat(2, 100, 5000),
            ];
        });
    }
}
