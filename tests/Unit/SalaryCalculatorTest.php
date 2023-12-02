<?php

namespace Tests\Unit;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Enum\AttendanceType;
use App\Models\Holiday;
use App\Models\Salary;
use App\Models\SalaryCalculation\SalaryCalculator;
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
    }

    /** @test */
    public function salary_day_is_on_15th_and_last_day_of_the_month(): void
    {
        $fifteen = Carbon::parse('2023-11-15');
        $this->assertEquals(15, $this->createCalculator($fifteen)->firstSalaryDay());

        $lastDay = Carbon::parse('2023-11-30');
        $this->assertEquals(30, $this->createCalculator($lastDay)->secondSalaryDay());
    }

    /** @test */
    public function if_salary_day_falls_on_weekend_any_week_days_before_is_salary_day(): void
    {
        $sunday = Carbon::parse('2023-10-15');
        $this->assertEquals(13, $this->createCalculator($sunday)->firstSalaryDay());

        $saturday = Carbon::parse('2023-09-30');
        $this->assertEquals(29, $this->createCalculator($saturday)->secondSalaryDay());
    }

    /** @test */
    public function if_salary_day_falls_on_holiday_a_day_before_should_be_salary_day(): void
    {
        $friday = Carbon::parse('2023-12-15');
        Holiday::factory()->create(['date' => $friday]);
        $this->assertEquals(14, $this->createCalculator($friday)->firstSalaryDay());

        $thursday = Carbon::parse('2023-11-30');
        Holiday::factory()->create(['date' => $thursday]);
        $this->assertEquals(29, $this->createCalculator($thursday)->secondSalaryDay());
    }

    /** @test */
    public function salary_day_should_not_be_weekend_or_holiday()
    {
        $sunday = Carbon::parse('2023-10-15');
        $friday = Holiday::factory()->create(['date' => $sunday->copy()->subDays(2)]);
        $thursday = Holiday::factory()->create(['date' => $sunday->copy()->subDays(3)]);
        $this->assertEquals(11, $this->createCalculator($sunday)->firstSalaryDay());

        $sunday = Carbon::parse('2023-12-31');
        $friday = Holiday::factory()->create(['date' => $sunday->copy()->subDays(2)]);
        $this->assertEquals(28, $this->createCalculator($sunday)->secondSalaryDay());
    }

    /** @test */
    public function it_can_determine_if_today_should_calculate_salary()
    {
        $monday = Carbon::parse('2023-11-13');
        $this->assertFalse($this->createCalculator($monday)->isSalaryDay());

        $tuesday = Carbon::parse('2023-11-14');
        $this->assertFalse($this->createCalculator($tuesday)->isSalaryDay());

        $wednesday = Carbon::parse('2023-11-15');
        $this->assertTrue($this->createCalculator($wednesday)->isSalaryDay());

        $thursday = Carbon::parse('2023-11-16');
        $this->assertFalse($this->createCalculator($thursday)->isSalaryDay());

        $friday = Carbon::parse('2023-11-17');
        $this->assertFalse($this->createCalculator($friday)->isSalaryDay());
    }

    /** @test */
    public function it_can_get_the_cover_period(): void
    {
        $friday = Carbon::parse('2023-11-17');
        $cover = $this->createCalculator($friday)->cover();
        $this->assertSame($cover[0]->toDateString(), '2023-11-16');
        $this->assertSame($cover[1]->toDateString(), '2023-11-30');

        $friday = Carbon::parse('2023-11-13');
        Carbon::setTestNow($friday);
        $cover = $this->createCalculator($friday)->cover();
        $this->assertSame($cover[0]->toDateString(), '2023-11-01');
        $this->assertSame($cover[1]->toDateString(), '2023-11-16');
    }

    /** @test */
    public function it_can_get_shifts_from_the_cover_period(): void
    {
        $friday = Carbon::parse('2023-11-17');
        $calculator = $this->createCalculator($friday);

        $shift = $this->createShift($friday);
        $yesterdayShift = $this->createShift($friday->subDays(3));

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

        $shift = $this->createHalfDayShift($date);
        $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($end = $date->copy()->setTime(12, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is between 4 to 8 hours, half day salary',
            $salaryPerDay / 2,
            [$start->toDateTimeString(), $end->toDateTimeString()],
            $shift
        );
    }

    /** @test */
    public function half_day_shift_should_not_consider_holiday_salary()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();
        Holiday::factory()->create(['date' => $date]);

        $shift = $this->createHalfDayShift($date);
        $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id);
        $this->createAttendance($end = $date->copy()->setTime(12, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is between 4 to 8 hours, half day salary',
            $salaryPerDay / 2,
            [$start->toDateTimeString(), $end->toDateTimeString()],
            $shift
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
        $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($end = $date->copy()->setTime(17, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is between 8 - 12 hour, normal daily salary',
            $salaryPerDay,
            [$start->toDateTimeString(), $end->toDateTimeString()],
            $shift
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
        $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($end = $date->copy()->setTime(20, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is euq or more than 12 hours, add 100 currently',
            $salaryPerDay + 100,
            [$start->toDateTimeString(), $end->toDateTimeString()],
            $shift
        );
    }

    /** @test */
    public function it_can_calculate_staff_full_day_8hour_salary_with_holiday()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($end = $date->copy()->setTime(17, 0), $shift->id, 1);
        $holiday = Holiday::factory()->create(['date' => $start->toDateString()]);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is between 8 - 12 hour, normal daily salary with holiday rate ' . $holiday->rate,
            $salaryPerDay + $salaryPerDay * $holiday->rate,
            [$start->toDateTimeString(), $end->toDateTimeString()],
            $shift
        );
    }

    /** @test */
    public function it_can_calculate_staff_12hour_salary_with_holiday()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary + 100;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($end = $date->copy()->setTime(20, 0), $shift->id, 1);
        $holiday = Holiday::factory()->create(['date' => $start->toDateString()]);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is euq or more than 12 hours, add 100 currently with holiday rate ' . $holiday->rate,
            $salaryPerDay + $salaryPerDay * $holiday->rate,
            [$start->toDateTimeString(), $end->toDateTimeString()],
            $shift
        );
    }

    /** @test */
    public function if_no_attendance_record_it_calculate_as_zero_salary()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $this->staff->daily_salary + 100;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'cant find punch detail, absence no salary of course',
            0,
            [null, null],
            $shift
        );
    }

    /** @test */
    public function if_staff_only_has_punch_in_record_it_calculates_as_the_shift_salary()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createHalfDayShift($date);
        $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'no punch out detail, get 8 hour salary',
            $salaryPerDay / 2,
            [$start->toDateTimeString(), null],
            $shift
        );
    }

    /** @test */
    public function if_staff_only_has_punch_out_record_it_calculates_as_8hour_salary()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $this->createAttendance($end = $date->copy()->setTime(20, 0), $shift->id, AttendanceType::out->value);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'no punch in detail, get 8 hour salary',
            $salaryPerDay,
            [null, $end->toDateTimeString()],
            $shift
        );
    }

    /** @test */
    public function if_staff_no_attendance_record_then_no_salary()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'cant find punch detail, absence no salary of course',
            0,
            [null, null],
            $shift
        );
    }

    /** @test */
    public function it_cant_get_salary_if_staff_working_hour_is_less_than_shift_hour()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $fifteen = Carbon::parse('2023-11-15');
        $calculator = $this->createCalculator($fifteen);
        $date = $fifteen->subDay();

        $shift = $this->createShift($date, [
            'to' => $date->copy()->setTime(8, 0)->toDateTimeString(),
            'from' => $date->copy()->setTime(20, 0)->toDateTimeString(),
        ]);

        $this->createAttendance($start = $date->copy()->setTime(8, 0), $shift->id);
        $this->createAttendance($date->setTime(9, 0), $shift->id);
        $this->createAttendance($date->setTime(11, 0), $shift->id, 1);
        $this->createAttendance($end = $date->copy()->setTime(17, 0), $shift->id, 1);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is 9 less than shift hour 12 no salary',
            0,
            [$start->toDateTimeString(), $end->toDateTimeString()],
            $shift
        );
    }

    /** @test */
    public function it_can_get_salary_if_shift_date_is_near_the_weekend()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $salaryDay = Carbon::parse('2023-12-29');
        $calculator = $this->createCalculator($salaryDay);
        //shift after salary day but still within salary coverage
        $sunday = $salaryDay->addDays(2);

        $shift = $this->createShift($sunday);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is between 8 - 12 hour, normal daily salary salary without works on ' . $shift->date->toDateString(),
            $salaryPerDay,
            [null, null],
            $shift
        );
    }

    /** @test */
    public function it_can_not_get_holiday_salary_if_shift_date_is_near_the_weekend_on_a_half_day_shift()
    {
        $this->assertDatabaseCount('salaries', 0);
        $this->assertDatabaseCount('salary_details', 0);

        $salaryPerDay = $this->staff->daily_salary;
        $salaryDay = Carbon::parse('2023-12-29');
        $calculator = $this->createCalculator($salaryDay);
        //shift after salary day but still within salary coverage
        $sunday = $salaryDay->addDays(2);
        Holiday::factory()->create(['date' => $sunday]);

        $shift = $this->createHalfDayShift($sunday);
        $calculator->calculate();

        $this->assertSalaryCorrect(
            $calculator,
            'working hour is between 4 to 8 hours, half day salary salary without works on ' . $shift->date->toDateString(),
            $salaryPerDay / 2,
            [null, null],
            $shift
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

    public function createShift($date, $attributes = [])
    {
        return Shift::factory()->create(array_merge([
            'date' => $date->toDateString(),
            'staff_id' => $this->staff->id
        ], $attributes));
    }

    public function createHalfDayShift($date, $attributes = [])
    {
        return $this->createShift($date,[
                'from' => $date->copy()->setTime(8, 0)->toDateTimeString(),
                'to' => $date->copy()->setTime(12, 0)->toDateTimeString(),
            ] + $attributes);
    }

    public function assertSalaryCorrect($calculator, $description, $amount, $date, $shift)
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
            $this->assertEquals($date[0], $detail['from']);
            $this->assertEquals($date[1], $detail['to']);
            $this->assertEquals($shift->id, $detail['shift_id']);
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
