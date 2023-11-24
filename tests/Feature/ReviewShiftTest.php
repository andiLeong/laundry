<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Enum\AttendanceType;
use App\Models\Shift;
use App\Models\Staff;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReviewShiftTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $date = today();
        $this->branch = Branch::factory()->create();
        $this->user = $this->staff(['branch_id' => $this->branch->id]);
        $this->staff = Staff::factory()->create([
            'branch_id' => $this->user->branch_id,
            'user_id' => $this->user->id
        ]);
        $this->shift = Shift::factory()->create([
            'staff_id' => $this->staff->id,
            'from' => $date->copy()->hour('09:00')->minute("00")->second(0),
            'to' => $date->copy()->hour('18:00')->minute("00")->second(0),
            'date' => $date->toDateString(),
        ]);
        $this->shift = $this->shift->fresh();
    }

    /** @test */
    public function if_punch_in_between_shift_and_no_punch_in_prior_shift_mark_as_late(): void
    {
        $this->assertFalse($this->shift->late);
        $this->attendance($this->shift->from->addMinutes());
        $this->artisan('shift:review');

        $this->assertTrue($this->shift->fresh()->late);
    }

    /** @test */
    public function if_punch_in_prior_shift_and_punch_in_between_shift_consider_not_late()
    {
        $this->assertFalse($this->shift->late);
        $this->attendance($this->shift->from->addHours());
        $this->attendance($this->shift->from->subMinutes());

        $this->artisan('shift:review');

        $this->assertFalse($this->shift->fresh()->late);
    }

    /** @test */
    public function if_current_punch_in_late_and_the_last_punch_in_from_the_shift_start_is_over_3_hour_consider_late()
    {
        $this->assertFalse($this->shift->late);
        $this->attendance($this->shift->from->subHours(4));
        $this->attendance($this->shift->from->addMinutes());

        $this->artisan('shift:review');

        $this->assertTrue($this->shift->fresh()->late);
    }

    /** @test */
    public function if_current_punch_in_is_3_hour_less_than_shift_starts_it_consider_not_late()
    {
        $this->assertFalse($this->shift->late);
        $this->attendance($this->shift->from->subHours(3));
        $this->attendance($this->shift->from->addMinutes());

        $this->artisan('shift:review');

        $this->assertFalse($this->shift->fresh()->late);
    }

    /** @test */
    public function if_no_punch_in_during_shift_and_no_punch_in_3_hours_before_shift_start_consider_absence()
    {
        $this->assertFalse($this->shift->absence);
        $this->artisan('shift:review');
        $this->assertTrue($this->shift->fresh()->absence);
    }

    /** @test */
    public function if_punch_out_early_and_no_punch_out_after_shift_mark_as_early_leave(): void
    {
        $this->assertFalse($this->shift->early_leave);
        $this->attendance($this->shift->to->subMinutes(), AttendanceType::out);
        $this->artisan('shift:review');

        $this->assertTrue($this->shift->fresh()->early_leave);
    }

    /** @test */
    public function if_punch_out_after_shift_and_punch_out_between_shift_consider_not_leave_early()
    {
        $this->assertFalse($this->shift->early_leave);
        $this->attendance($this->shift->to->subMinutes(), AttendanceType::out);
        $this->attendance($this->shift->to->addHours(), AttendanceType::out);

        $this->artisan('shift:review');

        $this->assertFalse($this->shift->fresh()->early_leave);
    }

    /** @test */
    public function if_punch_out_early_and_the_latest_punch_out_from_the_shift_end_is_over_3_hour_consider_early_out()
    {
        $this->assertFalse($this->shift->early_leave);
        $this->attendance($this->shift->to->subHour(), AttendanceType::out);
        $this->attendance($this->shift->to->addHours(4), AttendanceType::out);

        $this->artisan('shift:review');

        $this->assertTrue($this->shift->fresh()->early_leave);
    }

    /** @test */
    public function if_punch_out_is_3_hour_less_than_shift_end_it_consider_not_early_leave()
    {
        $this->assertFalse($this->shift->late);
        $this->attendance($this->shift->to->subHours(3), AttendanceType::out);
        $this->attendance($this->shift->to->subMinutes(), AttendanceType::out);

        $this->artisan('shift:review');

        $this->assertFalse($this->shift->fresh()->late);
    }

    /** @test */
    public function if_a_shift_is_review_it_cant_be_review_again(): void
    {
        $this->assertFalse($this->shift->late);
        $this->assertFalse($this->shift->reviewed);
        $this->shift->update(['reviewed' => true]);
        $this->assertTrue($this->shift->fresh()->reviewed);

        $this->attendance($this->shift->from->addMinutes());
        $this->artisan('shift:review');

        $this->assertFalse($this->shift->fresh()->late);
    }

    /** @test */
    public function shift_is_reviewed_after_each_reviewed(): void
    {
        $this->assertFalse($this->shift->reviewed);
        $this->artisan('shift:review');
        $this->assertTrue($this->shift->fresh()->reviewed);
    }

    public function attendance($time = null, $type = AttendanceType::in)
    {
        return Attendance::factory()->create([
            'staff_id' => $this->staff->id,
            'branch_id' => $this->staff->branch_id,
            'time' => $time ?? now(),
            'type' => $type->value,
        ]);
    }
}
