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

    protected function getPromotion(
        $name = 'sign up promotion',
        $class = 'App\\Models\\Promotions\\SignUpDiscount',
        $isolated = false,
        $discount = 0.5
    )
    {
        $this->promotion = Promotion::factory()->create([
            'name' => $name,
            'class' => $class,
            'isolated' => $isolated,
            'discount' => $discount,
        ]);

        return $this->promotion;
    }

    protected function getWednesdayPromotion($isolated = false, $discount = 0.1)
    {
        return $this->getPromotion(
            'Wednesday Promotion',
            'App\Models\Promotions\WednesdayWasher',
            $isolated,
            $discount
        );
    }

    protected function rewardGiftCertificatePromotion($isolated = false, $discount = 0)
    {
        return $this->getPromotion(
            'reward gift cert',
            'App\Models\Promotions\RewardGiftCertificate',
            $isolated,
            $discount
        );
    }

    protected function unqualifiedPromotion($isolated = false, $discount = 0.5)
    {
        return $this->getPromotion(
            'unqualified for every one',
            'App\Models\Promotions\NotQualifiedPromotion',
            $isolated,
            $discount
        );
    }

    protected function unqualifiedPromotion2($isolated = false, $discount = 0.2)
    {
        return $this->getPromotion(
            'unqualified for every one 2nd',
            'App\Models\Promotions\NotQualifiedPromotion',
            $isolated,
            $discount
        );
    }
}
