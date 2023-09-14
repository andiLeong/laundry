<?php

namespace Tests\Unit;

use App\Models\Branch;
use App\Models\Shift;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_contains_the_following_attributes(): void
    {
        $branch = Branch::factory()->create();
        $staff = $this->staff(['branch_id' => $branch->id]);
        $date = now();
        $from = $date->copy()->hour('09')->minute("00")->second(0);
        $to = $date->copy()->hour('18')->minute("00")->second(0);
        $shift = new Shift();
        $shift->from = $from;
        $shift->to = $to;
        $shift->date = $date->toDateString();
        $shift->staff_id = $staff->id;
        $shift->branch_id = $staff->branch_id;
        $shift->save();

        $shift = Shift::find($shift->id);

        $this->assertNotNull($shift);
        $this->assertEquals($from->toDateTimeString(), $shift->from->toDateTimeString());
        $this->assertEquals($to->toDateTimeString(), $shift->to->toDateTimeString());
        $this->assertEquals($date->toDateString(), $shift->date->toDateString());
        $this->assertEquals($staff->id, $shift->staff_id);
        $this->assertEquals($staff->branch_id, $shift->branch_id);
    }
}
