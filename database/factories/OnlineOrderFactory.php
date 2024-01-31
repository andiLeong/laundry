<?php

namespace Database\Factories;

use App\Models\Address;
use App\Models\Enum\OnlineOrderStatus;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OnlineOrder>
 */
class OnlineOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'address_id' => Address::factory(),
            'order_id' => Order::factory(),
            'status' => OnlineOrderStatus::PENDING_PICKUP->value,
            'delivery_fee' => 0,
            'delivery' => now()->addHours(12),
            'pickup' => now()->addHours(1),
            'pickup_at' => now()->addHours(2),
            'deliver_at' => now()->addHours(5),
        ];
    }
}
