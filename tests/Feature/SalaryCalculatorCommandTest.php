<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Shift;
use App\Models\Staff;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalaryCalculatorCommandTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
    }

    /** @test */
    public function it_can_calculate_staff_salary(): void
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $this->prepareWork(Carbon::parse('2023-11-15'))
            ->artisan('salary:calculate');

        $this->assertDatabaseCount('salaries', 2);
        $this->assertDatabaseCount('salary_details', 2);
    }

    public function prepareWork($salaryDate, $staffCount = 2)
    {
        User::factory($staffCount)
            ->create()
            ->each(function ($user) {
                Staff::factory()->create(['user_id' => $user->id, 'branch_id' => $this->branch->id]);
            });
        $staffs = Staff::all();
        $date = $salaryDate->copy()->subDay();
        Carbon::setTestNow($salaryDate);

        foreach ($staffs as $staff) {
            $shift = $this->createShift($date, [
                'staff_id' => $staff->id
            ]);
            $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id, $staff);
            $this->createAttendance($end = $date->copy()->setTime(17, 0), $shift->id, $staff, 1);
        }

        return $this;
    }

    public function createAttendance($time, $shiftId, $staff, $type = 0)
    {
        return Attendance::factory()->create([
            'staff_id' => $staff->id,
            'branch_id' => $staff->branch_id,
            'time' => $time,
            'type' => $type,
            'shift_id' => $shiftId,
        ]);
    }

    public function createShift($date, $attributes = [])
    {
        return Shift::factory()->create(array_merge([
            'date' => $date->toDateString(),
        ], $attributes));
    }
}
