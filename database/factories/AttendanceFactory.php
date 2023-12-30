<?php

namespace Database\Factories;

use App\Models\Branch;
use App\Models\Enum\AttendanceType;
use App\Models\Enum\UserType;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $branch = Branch::factory()->create();
        $staff = User::factory()->create([
            'type' => UserType::EMPLOYEE->value,
            'branch_id' => $branch->id,
        ]);

        return [
            'branch_id' => $staff->branch_id,
            'staff_id' => $staff->id,
            'type' => AttendanceType::IN->value,
            'time' => now(),
        ];
    }
}
