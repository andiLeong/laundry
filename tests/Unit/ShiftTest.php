<?php

namespace Tests\Unit;

use App\Models\Shift;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ShiftTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_contains_the_following_attributes(): void
    {
        $shift = new Shift();
        $shift->from = '09:00';
        $shift->to = '18:00';
        $shift->days = [1, 2, 3, 4, 5];
        $shift->off = [6, 7];
        $shift->save();

        $shift = Shift::find($shift->id);

        $this->assertNotNull($shift);
        $this->assertEquals('09:00', $shift->from);
        $this->assertEquals('18:00', $shift->to);
        $this->assertEquals([1, 2, 3, 4, 5], $shift->days);
        $this->assertEquals([6,7], $shift->off);
    }
}
