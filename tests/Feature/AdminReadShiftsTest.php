<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Shift;
use App\Models\Staff;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReadShiftsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/shifts';

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
        $this->user = $this->staff(['branch_id' => $this->branch->id]);
        $this->staff = Staff::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->user->branch_id
        ]);
    }

    /** @test */
    public function it_can_get_staff_shifts(): void
    {
        $shift = Shift::factory()->create(['staff_id' => $this->staff->id]);
        $shift2 = Shift::factory()->create();
        $ids = $this->fetch([], $this->user)->assertOk()->collect()->pluck('id');

        $this->assertTrue($ids->contains($shift->id));
        $this->assertFalse($ids->contains($shift2->id));
    }

    /** @test */
    public function by_default_it_gets_current_month_shifts(): void
    {
        $shift = Shift::factory()->create(['staff_id' => $this->staff->id]);
        $shift2 = Shift::factory()->create([
            'staff_id' => $this->staff->id,
            'date' => today()->subMonth()->toDateString(),
        ]);
        $ids = $this->fetch([], $this->user)->collect()->pluck('id');

        $this->assertTrue($ids->contains($shift->id));
        $this->assertFalse($ids->contains($shift2->id));
    }


    /** @test */
    public function it_can_filter_by_year_month(): void
    {
        $shift = Shift::factory()->create(['staff_id' => $this->staff->id]);
        $shift2 = Shift::factory()->create([
            'staff_id' => $this->staff->id,
            'date' => today()->subMonth()->toDateString(),
        ]);
        $ids = $this->fetch([
            'year' => today()->subMonth()->year,
            'month' => today()->subMonth()->month
        ], $this->user)->collect()->pluck('id');

        $this->assertTrue($ids->contains($shift2->id));
        $this->assertFalse($ids->contains($shift->id));
    }

    /** @test */
    public function it_can_read_first_and_last_day_of_the_month(): void
    {
        $shift = Shift::factory()->create(['staff_id' => $this->staff->id, 'date' => '2024-01-01']);
        $shift2 = Shift::factory()->create([
            'staff_id' => $this->staff->id,
            'date' => '2024-02-01'
        ]);
        $shift3 = Shift::factory()->create([
            'staff_id' => $this->staff->id,
            'date' => '2024-02-29'
        ]);
        $ids = $this->fetch([
            'year' => 2024,
            'month' => 2,
        ], $this->user)->collect()->pluck('id');

        $this->assertTrue($ids->contains($shift2->id));
        $this->assertTrue($ids->contains($shift3->id));
        $this->assertFalse($ids->contains($shift->id));
    }

    /** @test */
    public function only_admin_or_employee_can_access()
    {
        $this->signIn()->getJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function only_login_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
    }

    protected function fetch($query = [], $as = null)
    {
        return $this->fetchAsStaff($query, $as);
    }
}
