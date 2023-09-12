<?php

namespace Database\Factories;

use App\Models\Enum\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Shift>
 */
class ShiftFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'staff_id' => User::factory()->create([
                'type' => UserType::employee->value
            ])->id,
            'from' => '09:00',
            'to' => '18:00',
            'days' => [1, 2, 3, 4, 5],
            'off' => [6, 7],
        ];
    }
}
