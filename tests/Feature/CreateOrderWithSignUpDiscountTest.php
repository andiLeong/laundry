<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\OrderCanBeCreated;
use Tests\TestCase;

class CreateOrderWithSignUpDiscountTest extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderCanBeCreated;

    /** @test */
    public function it_can_create_order_with_signup_discount_promotion(): void
    {
        $this->getPromotion();
        $this->createOrderWithPromotionsAndMock([$this->promotion->id]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(100, $order->amount);
    }

    /** @test */
    public function it_cant_create_order_if_user_is_not_qualify(): void
    {
        $this->getPromotion();
        $user = Order::factory()->create()->user;
        $service = $this->getService();

        $this->assertDatabaseCount('order_promotions', 0);
        $this->assertDatabaseCount('orders', 1);

        $response = $this->createOrder([
            'promotion_ids' => [$this->promotion->id],
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $this->assertValidateMessage('Sorry You are not qualified with these promotions',$response,'promotion_ids');
        $this->assertDatabaseCount('orders', 1);
        $this->assertDatabaseCount('order_promotions', 0);
    }

    /** @test */
    public function it_cant_create_order_if_service_is_not_full_service(): void
    {
        $this->getPromotion();
        $service = Service::factory()->create(['full_service' => false]);

        $this->assertDatabaseCount('order_promotions', 0);
        $this->assertDatabaseCount('orders', 0);

        $response = $this->createOrder([
            'promotion_ids' => [$this->promotion->id],
            'service_id' => $service->id,
            'user_id' => User::factory()->create()->id,
        ]);

        $this->assertValidateMessage('Sorry You are not qualified with these promotions',$response,'promotion_ids');
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_promotions', 0);
    }
}
