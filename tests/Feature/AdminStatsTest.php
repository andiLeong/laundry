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
    public function it_can_get_order_count_x_days(): void
    {
        Order::factory()->create();
        Order::factory()->create(['created_at' => today()->subDays(8)]);
        Order::factory()->create(['created_at' => today()->subDays(7)]);
        Order::factory()->create(['created_at' => today()->subDays(6)]);
        Order::factory()->create(['created_at' => today()->subDays(5)]);
        $response = $this->fetch(['days' => 7]);
        $this->assertEquals(3, $response['order_count']);
    }

    /** @test */
    public function it_can_get_order_promotion_rate(): void
    {
        $promotion = Promotion::factory()->create();
        $orders = Order::factory(10)->create();
        $orders->take(7)->each(function ($order) use ($promotion) {
            OrderPromotion::insertByPromotions([$promotion], $order);
        });
        $response = $this->fetch();
        $this->assertEquals(7 / $orders->count() * 100, $response['order_promotion_rate']);
    }

    /** @test */
    public function it_can_get_order_promotion_rate_in_x_days(): void
    {
        $promotion = Promotion::factory()->create(['created_at' => today()->subDays(10)]);
        $orders = Order::factory(3)->create(['created_at' => today()->subDays(7)]);
        $orders->each(function ($order) use ($promotion) {
            OrderPromotion::insertByPromotions([$promotion], $order);
        });

        $promotion = Promotion::factory()->create();
        $orders = Order::factory(10)->create();
        $orders->take(7)->each(function ($order) use ($promotion) {
            OrderPromotion::insertByPromotions([$promotion], $order);
        });
        $response = $this->fetch(['days' => 7]);
        $this->assertEquals(7 / $orders->count() * 100, $response['order_promotion_rate']);
    }

    /** @test */
    public function if_no_order_order_promotion_count_should_return_zero(): void
    {
        $response = $this->fetch();
        $this->assertEquals(0, $response['order_promotion_rate']);
    }

    /** @test */
    public function order_promotion_count_only_round_up_2_digits(): void
    {
        $promotion = Promotion::factory()->create();
        $orders = Order::factory(11)->create();
        $orders->take(3)->each(function ($order) use ($promotion) {
            OrderPromotion::insertByPromotions([$promotion], $order);
        });
        $response = $this->fetch();
        $this->assertEquals(
            round(3 / 11, 2) * 100,
            $response['order_promotion_rate']
        );
    }

    /** @test */
    public function it_can_get_total_order_amount(): void
    {
        $order1 = Order::factory()->create(['total_amount' => 200]);
        $order2 = Order::factory()->create(['total_amount' => 70]);
        $order3 = Order::factory()->create(['total_amount' => 120]);
        $order4 = Order::factory()->create(['total_amount' => 100.56]);
        $response = $this->fetch();
        $this->assertEquals(
            $order1->total_amount + $order2->total_amount + $order3->total_amount + $order4->total_amount,
            $response['total_order_amount']
        );
    }

    /** @test */
    public function it_can_get_total_order_amount_in_x_days(): void
    {
        $order1 = Order::factory()->create(['created_at' => now(), 'total_amount' => 200]);
        $order2 = Order::factory()->create(['created_at' => today()->subDays(6), 'total_amount' => 70]);
        $order3 = Order::factory()->create(['created_at' => now(), 'total_amount' => 120]);
        $order4 = Order::factory()->create(['created_at' => today()->subDays(7), 'total_amount' => 100.56]);
        $response = $this->fetch(['days' => 7]);
        $this->assertEquals(
            $order1->total_amount + $order2->total_amount + $order3->total_amount,
            $response['total_order_amount']
        );
    }

    /** @test */
    public function only_authenticated_admin_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
        $this->be($this->staff())->getJson($this->endpoint)->assertForbidden();
    }

    public function fetch($query = [])
    {
        $query = '?' . http_build_query($query);
        return $this->signInAsAdmin()->getJson($this->endpoint . $query)->assertStatus(200)->collect();
    }
}
