<?php

namespace Database\Factories;

use App\Models\Enum\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Staff>
 */
class StaffFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $staff = User::factory()->create([
            'type' => UserType::employee->value,
        ]);
        return [
            'user_id' => $staff->id,
            'daily_salary' => 573,
            'full_time' => 1,
            'is_active' => 1,
        ];
    }
}
