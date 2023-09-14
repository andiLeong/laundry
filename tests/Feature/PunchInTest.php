<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Branch;
use App\Models\Enum\AttendanceType;
use App\Models\Shift;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $this->shift = Shift::factory()->create([
            'staff_id' => $this->user->id,
        ]);
        $this->type = AttendanceType::in->value;
        //obtain staff location, and branch location, within 1km only count as valid punch in
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
    public function staff_cant_perform_punch_in_if_their_location_its_out_of_range()
    {
        $this->markTestSkipped();
        dump($this->branch->toArray());

//        $length = strlen('tp1qaz2WSX3EDC4rfv');
//        dump($length);
//        dump(0 && 0);

        $query = 'select *,
            ST_Distance(
                ST_SRID(Point(longitude, latitude), 4326),
                ST_SRID(Point(121.01361883068033, 14.567702111099415), 4326)
            ) as distance
         from `branches`)';

        $res = DB::select($query);

        dd($res);

        $query = Branch::query();

        $distance = 1; // user input distance
        $user_latitude = '14.567702111099415'; // user input latitude
        $user_longitude = '121.01361883068033'; // user input logtitude

        $query->select('*')->selectRaw('
            ST_Distance(
               ST_SRID(Point(longitude, latitude), 4326),
               ST_SRID(Point(?, ?), 4326)
            ) as distance
        ', [$user_longitude, $user_latitude]);

        dd($query->get());

        $sql = "SELECT ROUND(6371 * acos (cos ( radians($user_latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($user_longitude) ) + sin ( radians($user_latitude) ) * sin( radians( latitude ) ))) AS distance,
       branches.*
FROM branches
where id = 1 HAVING distance <= $distance";

        $res = DB::select($sql);

        dd($res);

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
