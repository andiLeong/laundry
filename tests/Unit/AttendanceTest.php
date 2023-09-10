<?php

namespace Tests\Unit;


use App\Models\Attendance;
use App\Models\Enum\AttendanceType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_has_the_following_attributes(): void
    {
        $user = $this->customer();
        $attendance = new Attendance();
        $attendance->type = AttendanceType::in->value;
        $attendance->staff_id = $user->id;
        $attendance->time = $now = now();
        $attendance->save();
        $attendance = Attendance::find($attendance->id);

        $this->assertNotNull($attendance);
        $this->assertEquals($user->id, $attendance->staff_id);
        $this->assertEquals($now->toDateString(), $attendance->time->toDateString());
        $this->assertEquals(AttendanceType::in->name, $attendance->type);
    }

    /** @test */
    public function it_belongs_to_a_staff()
    {
        $this->markTestSkipped();
    }
}
