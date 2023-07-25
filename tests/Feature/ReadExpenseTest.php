<?php

namespace Tests\Feature;

use App\Models\Expense;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadExpenseTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $endpoint = '/api/admin/expense';

    /** @test */
    public function it_cam_get_all_expense(): void
    {
        $expenses = Expense::factory(3)->create()->pluck('id')->toArray();
        $expensesData = $this
            ->signInAsAdmin()
            ->getJson($this->endpoint)
            ->assertStatus(200)
            ->collect('data');

        $expensesData->each(fn($expense) => $this->assertTrue(in_array($expense['id'], $expenses))
        );
    }

    /** @test */
    public function it_can_filter_by_year_month(): void
    {
        $today = Expense::factory()->create();
        $lastMonth = Expense::factory()->create([
            'created_at' => today()->subMonths()
        ]);
        $threeMonthsAgo = Expense::factory()->create([
            'created_at' => today()->subMonths(3)
        ]);

        $expensesData = $this
            ->signInAsAdmin()
            ->getJson($this->endpoint . '?year-month=' . $today->created_at->format('Y-m'))
            ->assertStatus(200)
            ->collect('data')->all();

        $this->assertTrue(in_array($today->id,$expensesData[0]));
        $this->assertFalse(in_array($lastMonth->id,$expensesData));
        $this->assertFalse(in_array($threeMonthsAgo->id,$expensesData));
    }

    /** @test */
    public function only_authenticated_admin_user_can_access(): void
    {
        $this->getJson($this->endpoint)->assertStatus(401);
        $this->signIn($this->staff())->getJson($this->endpoint)->assertStatus(403);
    }
}
