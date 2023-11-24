<?php

namespace Database\Factories;

use App\Models\Salary;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\SalaryDetail>
 */
class SalaryDetailFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'salary_id' => Salary::factory(),
            'amount' => 573,
            'hour' => 8,
            'description' => Str::random(),
            'from' => today(),
            'to' => today()->addHours(8),
        ];
    }
}
