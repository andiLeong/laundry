<?php

namespace Database\Factories;

use App\Models\Enum\LaundryType;
use App\Models\Enum\WashMode;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderLaundryDetail>
 */
class OrderLaundryDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),
            'weight' => 8,
            'dried_minutes' => 40,
            'type' => LaundryType::mixed_clothes->value,
            'wash_mode' => WashMode::normal->value,
            'extra_wash' => false,
            'extra_rinse' => false,
        ];
    }
}
