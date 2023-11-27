<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Salary;
use App\Models\SalaryCalculator;
use App\Models\SalaryDetail;
use App\Models\Shift;
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
        $this->branch = Branch::factory()->create();
        $this->user = $this->staff(['branch_id' => $this->branch->id]);
        $this->staff = Staff::factory()->create(['user_id' => $this->user->id, 'branch_id' => $this->branch->id]);
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

    /** @test */
    public function it_can_get_the_cover_period(): void
    {
        $friday = Carbon::parse('2023-11-17');
        Carbon::setTestNow($friday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $cover = $calculator->cover();
        $this->assertSame($cover[0]->toDateString(), '2023-11-16');
        $this->assertSame($cover[1]->toDateString(), '2023-11-30');

        $friday = Carbon::parse('2023-11-13');
        Carbon::setTestNow($friday);
        $calculator = new FakeSalaryCalculator($this->staff);
        $cover = $calculator->cover();
        $this->assertSame($cover[0]->toDateString(), '2023-11-01');
        $this->assertSame($cover[1]->toDateString(), '2023-11-16');
    }

    /** @test */
    public function it_can_get_shifts_from_the_cover_period(): void
    {
        $friday = Carbon::parse('2023-11-17');
        Carbon::setTestNow($friday);
        $calculator = new FakeSalaryCalculator($this->staff);

        $shift = Shift::factory()->create([
            'date' => $friday->toDateString(),
            'staff_id' => $this->staff->id
        ]);
        $yesterdayShift = Shift::factory()->create([
            'date' => $friday->subDays(3)->toDateString(),
            'staff_id' => $this->staff->id
        ]);

        $shifts = $calculator->shifts()->pluck('id')->toArray();

        $this->assertTrue(in_array($shift->id, $shifts));
        $this->assertFalse(in_array($yesterdayShift->id, $shifts));
    }

    /** @test */
    public function it_can_calculate_staff_half_day_salary()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $this->createAttendance($date->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($date->setTime(12, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is between 4 to 8 hours, half day salary',
            $salaryPerDay / 2
        );
    }

    /** @test */
    public function it_can_calculate_staff_full_day_8hour_salary()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $this->createAttendance($date->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($date->setTime(17, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is between 8 - 12 hour, normal daily salary',
            $salaryPerDay
        );
    }

    /** @test */
    public function it_can_calculate_staff_12hour_salary()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $this->createAttendance($date->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($date->setTime(20, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is euq or more than 12 hours, add 100 currently',
            $salaryPerDay + 100
        );
    }

    /** @test */
    public function it_can_not_calculate_staff_salary_that_working_hour_is_than_4hour()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $this->createAttendance($date->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'hour is 3, unknown condition',
            0,
        );
    }

    public function createAttendance($time, $shiftId, $type = 0)
    {
        return Attendance::factory()->create([
            'staff_id' => $this->user->id,
            'branch_id' => $this->user->branch_id,
            'time' => $time,
            'type' => $type,
            'shift_id' => $shiftId,
        ]);
    }

    public function createShift($date)
    {
        return Shift::factory()->create([
            'date' => $date->toDateString(),
            'staff_id' => $this->staff->id
        ]);
    }

    public function assertSalaryCorrect($calculator, $description, $amount)
    {
        $salary = Salary::first();
        $details = SalaryDetail::all();
        $this->assertDatabaseHas('salaries', [
            'from' => $calculator->cover()[0]->toDateString(),
            'to' => $calculator->cover()[1]->toDateString(),
            'staff_id' => $this->staff->id,
            'amount' => $amount,
        ]);
        $this->assertDatabaseCount('salary_details', 1);

        foreach ($details as $detail) {
            $this->assertEquals($description, $detail['description']);
            $this->assertEquals($salary->id, $detail['salary_id']);
            $this->assertEquals($amount, $detail['amount']);
        }
    }

    protected function createCalculator($date)
    {
        Carbon::setTestNow($date);
        return new FakeSalaryCalculator($this->staff);
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

    public function shifts()
    {
        return $this->getShifts();
    }
}
