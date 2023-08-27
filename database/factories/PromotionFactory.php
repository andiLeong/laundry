<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

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
        $imgNumber = rand(1,4);
        $name = fake()->word() . rand(10000,99999);
        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'description' => fake()->paragraph(2),
            'status' => 1,
            'isolated' => 0,
            'start' => today()->subDays(),
            'until' => today()->addDays(7),
            'discount' => 0.5,
            'class' => 'App\\Models\\Promotions\\SignUpDiscount',
            'image' => 'https://andiliang.sgp1.cdn.digitaloceanspaces.com/sbin/promotion-'.$imgNumber.'-big.jpeg',
            'thumbnail' => 'https://andiliang.sgp1.cdn.digitaloceanspaces.com/sbin/promotion-'.$imgNumber.'.png',
        ];
    }
}
