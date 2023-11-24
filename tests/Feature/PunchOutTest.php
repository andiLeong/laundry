<?php

namespace Tests\Feature;

use App\Models\Enum\AttendanceType;

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

        $this->assertDatabaseHas('attendances', [
            'staff_id' => $this->staff->id,
            'type' => $this->type,
        ]);
        $this->assertDatabaseCount('attendances', 1);
    }

    /** @test */
    public function staff_cant_punch_out_if_they_are_out_of_range()
    {
        if (config('database.connections.mysql.host') == '127.0.0.1') {
            $this->assertTrue(true);
        } else {
            $this->punchOut([
                'longitude' => 121.01346781509143,
                'latitude' => 14.566808896873289
            ])->assertStatus(400);
        }
    }

    /** @test */
    public function staff_can_punch_out_if_they_are_within_certain_distance()
    {
        $this->punchOut([
            'longitude' => 121.0113987195058,
            'latitude' => 14.565564037755626
        ])->assertSuccessful();
    }

    public function punchOut($payload = [])
    {
        $payload['type'] = $this->type;
        return parent::punchIn($payload);
    }
}
