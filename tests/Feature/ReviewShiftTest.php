<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Shift;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
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
        $this->shift = Shift::factory()->create([
            'staff_id' => $this->user->id,
            'from' => $date->copy()->hour('09:00')->minute("00")->second(0),
            'to' => $date->copy()->hour('18:00')->minute("00")->second(0),
            'date' => $date->toDateString(),
        ]);
        $this->shift = $this->shift->fresh();
    }

    /** @test */
    public function it_can_mark_shift_as_late(): void
    {
        $this->assertFalse($this->shift->late);
        Carbon::setTestNow($this->shift->from->addMinutes());
        Attendance::factory()->create([
            'staff_id' => $this->user->id,
            'branch_id' => $this->user->branch_id
        ]);

        $this->artisan('shift:review');

        $this->assertTrue($this->shift->fresh()->late);
    }

    /** @test */
    public function it_is_not_late_if_punch_in_before_shift_and_punch_in_between_shift()
    {
        $this->assertFalse($this->shift->late);
        $this->attendance($this->shift->from->addHours());
        $this->attendance($this->shift->from->subMinutes());

        $this->artisan('shift:review');

        $this->assertFalse($this->shift->fresh()->late);
    }

    /** @test */
    public function if_current_punch_in_late_and_yesterday_got_shift_and_there_no_punch_in_after_yesterday_end_shift_and_it_should_mark_late()
    {
        $this->assertFalse($this->shift->late);
        $date = today()->subDay();
        $shift = Shift::factory()->create([
            'staff_id' => $this->user->id,
            'from' => $date->copy()->hour('09:00')->minute("00")->second(0),
            'to' => $date->copy()->hour('18:00')->minute("00")->second(0),
            'date' => $date->toDateString(),
        ]);

        $this->attendance($shift->from);
        $this->attendance($this->shift->from->addMinutes());

        $this->artisan('shift:review');

        $this->assertTrue($this->shift->fresh()->late);
    }

    /** @test */
    public function if_current_punch_in_late_and_yesterday_does_not_work_it_should_mark_as_late()
    {
        $this->assertFalse($this->shift->late);
        $this->attendance($this->shift->from->addMinutes());

        $this->artisan('shift:review');

        $this->assertTrue($this->shift->fresh()->late);
    }

    public function attendance($time = null)
    {
        return Attendance::factory()->create([
            'staff_id' => $this->user->id,
            'branch_id' => $this->user->branch_id,
            'time' => $time ?? now()
        ]);
    }
}
