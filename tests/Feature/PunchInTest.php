<?php

namespace Tests\Feature;

use App\Models\Branch;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PunchInTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $endpoint;

    protected function setUp(): void
    {
        parent::setUp();
        $this->endpoint = 'api/admin/attendance';

        $this->branch = Branch::factory()->create();
        $this->user = $this->customer(['branch_id' => $this->branch->id]);
        //obtain staff location, and branch location, within 1km only count as valid punch in
        //staff can punch in once per day
    }

    /** @test */
    public function staff_can_punch_in(): void
    {
        $this->markTestSkipped();
        $response = $this->get('/');

        $response->assertStatus(200);
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


        $distance = 1; // user input distance
        $user_latitude = '14.567702111099415'; // user input latitude
        $user_longitude = '121.01361883068033'; // user input logtitude

        $sql = "SELECT ROUND(6371 * acos (cos ( radians($user_latitude) ) * cos( radians( latitude ) ) * cos( radians( longitude ) - radians($user_longitude) ) + sin ( radians($user_latitude) ) * sin( radians( latitude ) ))) AS distance,
       branches.*
FROM branches
where id = 1 HAVING distance <= $distance";

        $res = DB::select($sql);

        dd($res);


    }
}
