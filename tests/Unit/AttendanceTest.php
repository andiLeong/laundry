<?php

namespace Tests\Unit;


use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Enum\AttendanceType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AttendanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->branch = Branch::factory()->create();
        $this->user = $this->staff(['branch_id' => $this->branch->id]);
    }

    /** @test */
    public function it_has_the_following_attributes(): void
    {
        $attendance = new Attendance();
        $attendance->type = AttendanceType::in->value;
        $attendance->staff_id = $this->user->id;
        $attendance->time = $now = now();
        $attendance->branch_id = $this->user->branch_id;
        $attendance->save();
        $attendance = Attendance::find($attendance->id);

        $this->assertNotNull($attendance);
        $this->assertEquals($this->user->id, $attendance->staff_id);
        $this->assertEquals($now->toDateString(), $attendance->time->toDateString());
        $this->assertEquals(AttendanceType::in->name, $attendance->type);
    }

    /** @test */
    public function it_belongs_to_a_staff()
    {
        $attendance = Attendance::factory()->create(['staff_id' => $this->user->id]);
        $this->assertEquals($this->user->id,$attendance->staff->id);
    }

    /** @test */
    public function it_belongs_to_a_branch()
    {
        $attendance = Attendance::factory()->create(['staff_id' => $this->user->id,'branch_id' => $this->branch->id]);
        $this->assertEquals($this->user->branch_id,$attendance->branch->id);
    }
}
