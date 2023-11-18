<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Enum\AttendanceType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadAttendanceTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->endpoint = 'api/admin/attendance';

        $this->branch = Branch::factory()->create();
        $this->user = $this->staff(['branch_id' => $this->branch->id]);
    }

    /** @test */
    public function only_authorized_user_can_access(): void
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
        $this->signIn($this->customer())->getJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function staff_can_only_read_its_attendance_record()
    {
        $john = $this->staff(['branch_id' => $this->branch->id, 'id' => 100]);
        $johnAttendance = Attendance::factory()->create([
            'staff_id' => $john->id,
            'branch_id' => $john->branch_id
        ]);
        $attendance = $this->attendance(2);

        $response = $this->fetch();

        $this->assertTrue(in_array($attendance[1]['id'], $response));
        $this->assertTrue(in_array($attendance[0]['id'], $response));
        $this->assertFalse(in_array($johnAttendance->id, $response));
    }

    /** @test */
    public function admin_can_only_read_all_attendance_record()
    {
        $john = $this->staff(['branch_id' => $this->branch->id, 'id' => 100]);
        $johnAttendance = Attendance::factory()->create([
            'staff_id' => $john->id,
            'branch_id' => $john->branch_id
        ]);
        $attendance = $this->attendance(2);

        $response = $this->fetch([], $this->admin());

        $this->assertTrue(in_array($johnAttendance->id, $response));
        $this->assertTrue(in_array($attendance[0]->id, $response));
        $this->assertTrue(in_array($attendance[1]->id, $response));
    }

    /** @test */
    public function it_can_filter_by_month()
    {
        $lastMonth = today()->startOfMonth()->subMonth();
        $lastMonthRecord = $this->attendance(null, ['time' => $lastMonth]);
        $today = $this->attendance(2, ['time' => today()]);

        $response = $this->fetch(['month' => $lastMonth->format('Y-m')]);
        $this->assertTrue(in_array($lastMonthRecord->id, $response));
        $this->assertFalse(in_array($today[0]->id, $response));
        $this->assertFalse(in_array($today[1]->id, $response));
    }

    /** @test */
    public function it_can_filter_by_staff_id()
    {
        $attendance = $this->attendance();
        $john = $this->staff(['branch_id' => $this->branch->id, 'id' => 100]);
        $johnAttendance = Attendance::factory()->create([
            'staff_id' => $john->id,
            'branch_id' => $john->branch_id
        ]);

        $response = $this->fetch(['staff_id' => $john->id]);
        $this->assertTrue(in_array($attendance->id, $response));
        $this->assertFalse(in_array($johnAttendance->id, $response));

        $response = $this->fetch(['staff_id' => $john->id], $this->admin());
        $this->assertFalse(in_array($attendance->id, $response));
        $this->assertTrue(in_array($johnAttendance->id, $response));
    }

    /** @test */
    public function only_certain_column_is_return()
    {
        $attendance = $this->attendance();
        $response = $this->signIn($this->user)->getJson($this->endpoint)->json('data')[0];

        $this->assertEquals($attendance->id, $response['id']);
        $this->assertEquals(AttendanceType::in->name, $response['type']);
        $this->assertEquals($attendance->time->toJson(), $response['time']);
        $this->assertEquals($attendance->staff->first_name, $response['staff']['first_name']);
        $this->assertEquals($attendance->staff->middle_name, $response['staff']['middle_name']);
        $this->assertEquals($attendance->staff->last_name, $response['staff']['last_name']);
        $this->assertEquals($attendance->branch->name, $response['branch_name']);
        $this->assertColumnsSame(['id', 'type', 'time', 'branch_name', 'staff'], array_keys($response));
        $this->assertColumnsSame(['first_name', 'middle_name', 'last_name'], array_keys($response['staff']));
    }

    protected function attendance($count = null, $attributes = [])
    {
        return Attendance::factory($count)->create(array_merge([
            'staff_id' => $this->user->id,
            'branch_id' => $this->user->branch_id
        ], $attributes));
    }

    protected function fetch($payload = [], $user = null)
    {
        return $this->fetchAsStaff($payload, $user ?? $this->user)
            ->collect('data')
            ->pluck('id')
            ->toArray();
    }
}
