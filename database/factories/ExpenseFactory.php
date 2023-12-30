<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Expense>
 */
class ExpenseFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => collect(['water','staff','electricity','rental'])->random(),
            'created_at' => today()->subMonths(rand(1,4)),
            'amount' => rand(100,5000),
//            'branch_id' => rand(1,10)
        ];
    }
}
