<?php

namespace Database\Factories;

use App\Models\Enum\OrderPayment;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\OrderPaid>
 */
class OrderPaidFactory extends Factory
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
            'creator_id' => User::factory(),
            'amount' => 170,
        ];
    }
}
