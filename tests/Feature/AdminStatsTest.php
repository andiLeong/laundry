<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminStatsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $endpoint = '/api/admin/stats';

    /** @test */
    public function it_can_get_user_count(): void
    {
        $users = User::factory(9)->create();
        User::factory(2)->create(['phone_verified_at' => null]);
        $response = $this->fetch();
        $this->assertEquals($users->count() + 1, $response['user_count']);
    }

    /** @test */
    public function it_can_get_order_count(): void
    {
        $orders = Order::factory(10)->create();
        $response = $this->fetch();
        $this->assertEquals($orders->count(), $response['order_count']);
    }

    /** @test */
    public function it_can_get_order_promotion_rate(): void
    {
        $promotion = Promotion::factory()->create();
        $orders = Order::factory(10)->create();
        $orders->take(7)->each(function ($order) use($promotion){
           OrderPromotion::insertByPromotions([$promotion],$order);
        });
        $response = $this->fetch();
        $this->assertEquals( 7 / $orders->count() * 100, $response['order_promotion_rate']);
    }

    /** @test */
    public function if_no_order_order_promotion_count_should_return_zero(): void
    {
        $response = $this->fetch();
        $this->assertEquals( 0, $response['order_promotion_rate']);
    }

    /** @test */
    public function order_promotion_count_only_round_up_2_digits(): void
    {
        $promotion = Promotion::factory()->create();
        $orders = Order::factory(11)->create();
        $orders->take(3)->each(function ($order) use($promotion){
            OrderPromotion::insertByPromotions([$promotion],$order);
        });
        $response = $this->fetch();
        $this->assertEquals(
            round(3/11,2) * 100,
            $response['order_promotion_rate']
        );
    }

    /** @test */
    public function it_can_get_total_order_amount(): void
    {
        $order1 = Order::factory()->create(['amount' => 200]);
        $order2 = Order::factory()->create(['amount' => 70]);
        $order3 = Order::factory()->create(['amount' => 120]);
        $order4 = Order::factory()->create(['amount' => 100.56]);
        $response = $this->fetch();
        $this->assertEquals(
            $order1->amount + $order2->amount + $order3->amount + $order4->amount,
            $response['total_order_amount']
        );
    }

    /** @test */
    public function only_authenticated_admin_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
        $this->be($this->staff())->getJson($this->endpoint)->assertForbidden();
    }

    public function fetch()
    {
        return $this->signInAsAdmin()->getJson($this->endpoint)->assertStatus(200)->collect();
    }
}