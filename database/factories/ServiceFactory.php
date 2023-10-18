<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->word() . rand(10000,99999);
        return [
            'name' => $name,
            'full_service' => 1,
            'primary' => 1,
            'up_to' => 8,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(1),
            'price' => rand(100,300)
        ];
    }
}
