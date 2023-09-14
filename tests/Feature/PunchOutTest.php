<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Enum\AttendanceType;
use App\Models\Shift;

class PunchOutTest extends PunchInTest
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->type = AttendanceType::out->value;
    }

    /** @test */
    public function staff_can_punch_out(): void
    {
        $this->assertDatabaseCount('attendances', 0);
        $this->punchOut()->assertSuccessful();

        $this->assertNotNull(Attendance::firstForToday($this->user->id, $this->type));
        $this->assertDatabaseHas('attendances',[
            'staff_id' => $this->user->id,
            'type' => $this->type,
            'is_late' => false
        ]);
        $this->assertDatabaseCount('attendances', 1);
    }

    /** @test */
    public function staff_cant_punch_out_if_there_is_no_shift_associate()
    {
        Shift::where('staff_id',$this->user->id)->delete();
        $message = $this->punchOut()->assertStatus(400)->json('message');
        $this->assertEquals('Opps You do not have shift associate', $message);
    }

    /** @test */
    public function staff_cant_punch_out_if_they_not_in_the_shop()
    {
        $this->markTestSkipped();
    }

    public function punchOut($payload = [])
    {
        $payload['type'] = $this->type;
        return parent::punchIn($payload);
    }
}
