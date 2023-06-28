<?php

namespace Tests;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;

trait OrderCanBeCreated
{
    protected string $endpoint = 'api/admin/order';
    protected $promotion;

    public function createOrder($overwrites = [])
    {
        return $this->signInAsAdmin()->postJson($this->endpoint,
            $this->orderAttributes($overwrites)
        );
    }

    protected function orderAttributes(mixed $overwrites)
    {
        $attributes = Order::factory()->make()->toArray();
        $attributes = $attributes + ['isolated' => 0];
        return array_merge($attributes, $overwrites);
    }

    public function createOrderWithPromotions(array $promotions, User $user = null)
    {
        $user ??= User::factory()->create();
        $service = $this->getService();

        $this->assertDatabaseCount('order_promotions', 0);
        $this->createOrder([
            'promotion_ids' => $promotions,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('order_promotions', count($promotions));
        $this->assertDatabaseCount('orders', 1);
    }

    public function getService($price = 200, $name = 'full service')
    {
        return Service::factory()->create([
            'price' => $price,
            'name' => $name
        ]);
    }

    protected function getPromotion($name = 'sign up promotion',$class = 'App\\Models\\Promotions\\SignUpDiscount')
    {
        $this->promotion = Promotion::factory()->create([
            'name' => $name,
            'class' => $class,
        ]);

        return $this->promotion;
    }
}
