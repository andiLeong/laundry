<?php

namespace Tests\Unit;

use App\Models\Promotion;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class PromotionTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_contains_necessary_attributes(): void
    {
        $promotion = new Promotion();
        $promotion->name = 'welcome';
        $promotion->description = 'new sign up user can avail 20% discount price';
        $promotion->status = 1;
        $promotion->start = '2023-06-01 00:00';
        $promotion->until = '2023-06-05 23:59';
        $promotion->isolated = 0;
        $promotion->class = 'app\\Models\\Promotions\\SignUp.php';
        $promotion->save();

        $this->assertEquals('welcome', $promotion->name);
        $this->assertEquals('new sign up user can avail 20% discount price', $promotion->description);
        $this->assertSame(true, $promotion->status);
        $this->assertSame(false, $promotion->isolated);
        $this->assertEquals('2023-06-01 00:00', $promotion->start->format('Y-m-d H:i'));
        $this->assertInstanceOf(Carbon::class, $promotion->start);
        $this->assertInstanceOf(Carbon::class, $promotion->until);
        $this->assertEquals('2023-06-05 23:59', $promotion->until->format('Y-m-d H:i'));
        $this->assertEquals('app\\Models\\Promotions\\SignUp.php', $promotion->class);
    }

    /** @test */
    public function it_can_get_until_attribute_property()
    {
        $promotion = Promotion::factory()->create([
            'until' => null
        ]);
        $this->assertNull($promotion->until);
    }

    /** @test */
    public function it_can_determine_if_promotion_runs_forever()
    {
        $foreverPromotion = Promotion::factory()->create([
            'until' => null
        ]);
        $promotion = Promotion::factory()->create([
            'until' => today()
        ]);
        $this->assertTrue($foreverPromotion->forever());
        $this->assertFalse($promotion->forever());
        $this->assertEquals(today()->format('Y-m-d H:i:s'), $promotion->getRawOriginal('until'));
    }

    /** @test */
    public function it_can_determine_if_promotion_is_expired()
    {
        $foreverPromotion = Promotion::factory()->create([
            'until' => null
        ]);
        $promotion = Promotion::factory()->create([
            'until' => today()->addDays(7)
        ]);
        $expiredPromotion = Promotion::factory()->create([
            'until' => today()->subDays(7)
        ]);

        $this->assertFalse($foreverPromotion->expired());
        $this->assertFalse($promotion->expired());
        $this->assertTrue($expiredPromotion->expired());
    }
}
