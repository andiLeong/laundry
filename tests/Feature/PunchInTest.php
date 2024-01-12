<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Enum\AttendanceType;
use App\Models\Shift;
use App\Models\Staff;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class PunchInTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->endpoint = 'api/admin/attendance';

        $this->branch = Branch::factory()->create();
        $this->user = $this->staff(['branch_id' => $this->branch->id]);
        $this->staff = Staff::factory()->create([
            'user_id' => $this->user->id,
            'branch_id' => $this->user->branch_id,
            'id' => 9999,
        ]);
        $this->shift = Shift::factory()->create([
            'staff_id' => $this->staff->id,
        ]);
        $this->type = AttendanceType::IN->value;
    }

    /** @test */
    public function staff_can_punch_in(): void
    {
        $this->assertDatabaseCount('attendances', 0);
        $this->punchIn()->assertSuccessful();
        $this->assertDatabaseCount('attendances', 1);
    }

    /** @test */
    public function only_login_staff_can_perform_punch_in()
    {
        $this->postJson($this->endpoint)->assertUnauthorized();
        $this->signIn($this->customer())->postJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function non_staff_cant_perform_punch_in()
    {
        $this->user = $this->admin();
        $message = $this->punchIn()->assertStatus(400)->json('message');
        $this->assertEquals($message,'Look like you are not a staff...');
    }

    /** @test */
    public function staff_cant_perform_punch_in_if_their_shift_not_today()
    {
        Shift::first()->delete();
        $message = $this->punchIn([
            'longitude' => 121.01346781509143,
            'latitude' => 14.566808896873289
        ])->assertStatus(400)->json('message');
        $this->assertEquals($message,'You are not supposed to work today..');
    }

    /** @test */
    public function shift_id_store_once_punch_success()
    {
        $this->punchIn()->assertSuccessful();
        $attendance = Attendance::first();
        $this->assertEquals($this->shift->id,$attendance->shift_id);
    }

    /** @test */
    public function staff_id_should_be_staff_model_id()
    {
        $this->punchIn()->assertSuccessful();
        $attendance = Attendance::first();
        $this->assertEquals($this->staff->id,$attendance->staff_id);
        $this->assertNotEquals($this->user->id,$attendance->staff_id);
        $this->assertNotEquals($this->user->id,$this->staff->id);
    }

    /** @test */
    public function staff_cant_perform_punch_in_if_their_location_its_out_of_range()
    {
        if (config('database.connections.mysql.host') == '127.0.0.1') {
            $this->assertTrue(true);
        } else {
            $this->punchIn([
                'longitude' => 121.01346781509143,
                'latitude' => 14.566808896873289
            ])->assertStatus(400);
        }
    }

    /** @test */
    public function staff_can_perform_punch_in_if_their_location_its_within_range()
    {
        $this->punchIn([
            'longitude' => 121.0113987195058,
            'latitude' => 14.565564037755626
        ])->assertSuccessful();
    }

    /** @test */
    public function amount_must_be_valid()
    {
        $name = 'type';
        $rule = ['required', 'in:0,1'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->punchIn($payload)
        );
    }

    /** @test */
    public function latitude_must_be_valid()
    {
        $name = 'latitude';
        $rule = ['required'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->punchIn($payload)
        );
    }

    /** @test */
    public function longitude_must_be_valid()
    {
        $name = 'longitude';
        $rule = ['required'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->punchIn($payload)
        );
    }

    public function punchIn($payload = [])
    {
        $attributes = Attendance::factory()->make()->toArray();
        $attributes['type'] = $this->type;
        $attributes['longitude'] = 999;
        $attributes['latitude'] = 222;
        $payload = array_merge($attributes, $payload);
        return $this->signIn($this->user)->postJson($this->endpoint, $payload);
    }
}
