<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Product>
 */
class ProductFactory extends Factory
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
            'slug' => Str::slug($name),
            'price' => rand(50,300),
            'stock' => rand(100,300),
        ];
    }
}
