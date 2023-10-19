<?php

namespace Database\Factories;

use App\Models\Enum\OrderPayment;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Order>
 */
class OrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(),
            'user_id' => User::factory(),
            'amount' => $amount = rand(120,150),
            'total_amount' => $amount,
            'product_amount' => 0,
            'creator_id' => User::factory(),
            'paid' => 1,
            'issued_invoice' => 0,
            'payment' => OrderPayment::cash->value,
        ];
    }
}
