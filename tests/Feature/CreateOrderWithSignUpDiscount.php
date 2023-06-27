<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\OrderCanBeCreated;
use Tests\TestCase;

class CreateOrderWithSignUpDiscount extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderCanBeCreated;

    /** @test */
    public function it_can_create_order_with_signup_discount_promotion(): void
    {
        $this->getPromotion();
        $this->createOrderWithPromotions([$this->promotion->id]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(140, $order->amount);
    }

    /** @test */
    public function it_cant_create_order_if_user_is_not_qualify(): void
    {
        $this->getPromotion();
        $user = Order::factory()->create()->user;
        $service = $this->getService();

        $this->assertDatabaseCount('order_promotions', 0);
        $this->assertDatabaseCount('orders', 1);

        $this->createOrder([
            'promotion_ids' => [$this->promotion->id],
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_promotions', 0);
    }
}
