<?php

namespace Database\Factories;

use App\Models\Enum\OrderPayment;
use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GcashOrder>
 */
class GcashOrderFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'order_id' => Order::factory()->create(['payment' => OrderPayment::GCASH->value]),
            'reference_number' => Str::random(32),
        ];
    }
}
