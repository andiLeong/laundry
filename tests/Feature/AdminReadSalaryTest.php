<?php

namespace Tests\Feature;

use App\Models\Salary;
use App\Models\Staff;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReadSalaryTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/salary';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->staff();
        $this->staff = Staff::factory()->create([
            'user_id' => $this->user->id,
            'id' => 999,
        ]);
    }

    /** @test */
    public function it_can_read_staff_own_monthly_salary(): void
    {
        $salary = Salary::factory()->create([
            'staff_id' => $this->staff->id
        ]);
        $user = $this->staff();
        $staff = Staff::factory()->create([
            'user_id' => $user->id,
            'id' => 888,
        ]);
        $salary2 = Salary::factory()->create([
            'staff_id' => $staff->id
        ]);
        $ids = $this->fetchSalaryIds();

        $this->assertTrue($ids->contains($salary->id));
        $this->assertFalse($ids->contains($salary2->id));
    }

    /** @test */
    public function it_can_view_all_if_admin_login(): void
    {
        $salary = Salary::factory()->create([
            'staff_id' => $this->staff->id
        ]);
        $user = $this->staff();
        $staff = Staff::factory()->create([
            'user_id' => $user->id,
            'id' => 888,
        ]);
        $salary2 = Salary::factory()->create([
            'staff_id' => $staff->id
        ]);
        $ids = $this->fetchSalaryIds([], $this->admin());

        $this->assertTrue($ids->contains($salary->id));
        $this->assertTrue($ids->contains($salary2->id));
    }

    protected function fetch($query = [], $as = null)
    {
        return $this->fetchAsStaff($query, $as);
    }

    public function fetchSalaryIds($query = [], $as = null)
    {
        $as ??= $this->user;
        return $this->fetch($query, $as)->collect('data')->pluck('id');
    }
}
