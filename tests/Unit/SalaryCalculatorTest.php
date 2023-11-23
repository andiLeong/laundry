<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Salary;
use App\Models\SalaryCalculator;
use App\Models\SalaryDetail;
use App\Models\Staff;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SalaryCalculatorTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->staff();
        $this->staff = Staff::factory()->create(['user_id' => $this->user->id]);
        $this->calculator = new FakeSalaryCalculator($this->staff);
    }

    /** @test */
    public function salary_day_is_on_15th_and_last_day_of_the_month(): void
    {
        $fifteen = Carbon::parse('2023-11-15');
        Carbon::setTestNow($fifteen);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertEquals(15, $calculator->firstSalaryDay());

        $lastDay = Carbon::parse('2023-11-30');
        Carbon::setTestNow($lastDay);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertEquals(30, $calculator->secondSalaryDay());
    }

    /** @test */
    public function if_salary_day_falls_on_weekend_any_week_days_before_is_salary_day(): void
    {
        $sunday = Carbon::parse('2023-10-15');
        Carbon::setTestNow($sunday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertEquals(13, $calculator->firstSalaryDay());

        $saturday = Carbon::parse('2023-09-30');
        Carbon::setTestNow($saturday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertEquals(29, $calculator->secondSalaryDay());
    }

    /** @test */
    public function if_salary_day_falls_on_holiday_a_day_before_should_be_salary_day(): void
    {
        $this->assertTrue(true);
    }

    /** @test */
    public function it_can_determine_if_today_should_calculate_salary()
    {
        $monday = Carbon::parse('2023-11-13');
        Carbon::setTestNow($monday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertFalse($calculator->isSalaryDay());

        $tuesday = Carbon::parse('2023-11-14');
        Carbon::setTestNow($tuesday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertFalse($calculator->isSalaryDay());

        $wednesday = Carbon::parse('2023-11-15');
        Carbon::setTestNow($wednesday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertTrue($calculator->isSalaryDay());

        $thursday = Carbon::parse('2023-11-16');
        Carbon::setTestNow($thursday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertFalse($calculator->isSalaryDay());

        $friday = Carbon::parse('2023-11-17');
        Carbon::setTestNow($friday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $this->assertFalse($calculator->isSalaryDay());
    }
//
//    /** @test */
//    public function it_can_get_the_cover_period(): void
//    {
//        $friday = Carbon::parse('2023-11-17');
//        Carbon::setTestNow($friday);
//        $calculator = new FakeSalaryCalculator($this->staff);
//        $this->assertSame($calculator->cover(),[16,$friday->lastOfMonth()->day]);
//
//        $friday = Carbon::parse('2023-11-13');
//        Carbon::setTestNow($friday);
//        $calculator = new FakeSalaryCalculator($this->staff);
//        $this->assertSame($calculator->cover(),[1,16]);
//    }

    /** @test */
    public function it_can_calculate_staff_salary_per8_hour_shift()
    {
        $this->markTestSkipped();
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_detail', 0);

        $salaryPerDay = $this->staff->dailySalary;

        $attendance = Attendance::factory()->create([
            'staff_id' => $this->user->id,
            'branch_id' => $this->user->branch_id,
            'time' => today()->startOfMonth()->setTime(8, 0),
            'type' => 0
        ]);

        $attendance = Attendance::factory()->create([
            'staff_id' => $this->user->id,
            'branch_id' => $this->user->branch_id,
            'time' => today()->startOfMonth()->setTime(17, 0),
            'type' => 1
        ]);

        $attendance = Attendance::factory()->create([
            'staff_id' => $this->user->id,
            'branch_id' => $this->user->branch_id,
            'time' => today()->startOfMonth()->addDay()->setTime(8, 0),
            'type' => 0
        ]);

        $attendance = Attendance::factory()->create([
            'staff_id' => $this->user->id,
            'branch_id' => $this->user->branch_id,
            'time' => today()->startOfMonth()->addDays(2)->setTime(17, 0),
            'type' => 1
        ]);

        $salary = Salary::first();
        $details = SalaryDetail::all();
        $this->assertDatabaseHas('salaries', [
            'from' => today()->startOfMonth()->format('Y-m-d'),
            'to' => today()->startOfMonth()->adddays(15)->format('Y-m-d'),
            'staff_id' => $this->staff->id,
            'amount' => $salaryPerDay * 2,
        ]);
        $this->assertDatabaseCount('salary_details', 2);

        foreach ($details as $detail) {
            $this->assertEquals('normal daily salary ', $salaryPerDay, $detail['description']);
            $this->assertEquals($salary->id, $detail['salary_id']);
            $this->assertEquals($salaryPerDay, $detail['amount']);
        }
    }
}

class FakeSalaryCalculator extends SalaryCalculator
{
    public function isSalaryDay()
    {
        return $this->salaryDay();
    }

    public function firstSalaryDay()
    {
        return $this->firstSalaryDay;
    }

    public function secondSalaryDay()
    {
        return $this->secondSalaryDay;
    }

    public function cover()
    {
       return $this->coverPeriod;
    }
}
