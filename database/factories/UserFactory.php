<?php

namespace Database\Factories;

use App\Models\Branch;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => 0,
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->name(),
            'last_name' => fake()->lastName(),
            'phone' => '09' . rand(1000,3000) . rand(30001,99999),
            'phone_verified_at' => now(),
            'branch_id' => null,
            'password' => 'password', // password
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }
}
