<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Place>
 */
class PlaceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'place_id' => Str::random(25),
            'address' => fake()->address(),
            'name' => fake()->city(),
            'longitude' => fake()->longitude(),
            'latitude' => fake()->latitude(),
        ];
    }
}
