<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\OrderCanBeCreated;
use Tests\TestCase;

class CreateOrderWithSignUpDiscount extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderCanBeCreated;

    private \Illuminate\Database\Eloquent\Collection|\Illuminate\Database\Eloquent\Model $promotion;

    /** @test */
    public function it_can_create_order_with_signup_discount_promotion(): void
    {
        $this->createPromotion();
        $this->createOrderWithPromotions([$this->promotion->id]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(140, $order->amount);
    }

    /** @test */
    public function it_cant_create_order_if_user_is_not_qualify(): void
    {
        $this->createPromotion();
        $user = Order::factory()->create()->user;
        $service = Service::factory()->create();

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

    public function createOrderWithPromotions(array $promotions, User $user = null)
    {
        $user ??= User::factory()->create();
        $service = Service::factory()->create(['price' => 200]);

        $this->assertDatabaseCount('order_promotions', 0);
        $this->createOrder([
            'promotion_ids' => $promotions,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('order_promotions', count($promotions));
        $this->assertDatabaseCount('orders', 1);
    }



    private function createPromotion()
    {
        $this->promotion = Promotion::factory()->create([
            'name' => 'sign up promotion',
            'class' => 'App\\Models\\Promotions\\SignUpDiscount',
        ]);
    }
}
