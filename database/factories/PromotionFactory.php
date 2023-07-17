<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Promotion>
 */
class PromotionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->word(),
            'description' => fake()->paragraph(2),
            'status' => 1,
            'isolated' => 0,
            'start' => today()->subDays(),
            'until' => today()->addDays(7),
            'discount' => 0.5,
            'class' => 'App\\Models\\Promotions\\SignUpDiscount',
        ];
    }
}
