<?php

namespace Database\Factories;

use App\Models\Branch;
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
        $branch = Branch::factory()->create();
        $staff = User::factory()->create([
            'type' => UserType::EMPLOYEE->value,
            'branch_id' => $branch->id
        ]);
        $date = now();
        return [
            'staff_id' => $staff->id,
            'branch_id' => $staff->branch_id,
            'from' => $date->copy()->hour('09:00')->minute("00")->second(0),
            'to' => $date->copy()->hour('18:00')->minute("00")->second(0),
            'date' => $date->toDateString(),
        ];
    }
}
