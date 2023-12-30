<?php

namespace Database\Factories;

use App\Models\Branch;
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
        $user = User::factory()->create([
            'type' => UserType::EMPLOYEE->value,
            'branch_id' => Branch::factory()->create()->id
        ]);
        return [
            'user_id' => $user->id,
            'branch_id' => $user->branch_id,
            'daily_salary' => 573,
            'full_time' => 1,
            'is_active' => 1,
        ];
    }
}
