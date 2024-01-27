<?php

namespace Tests;

use App\Models\OnlineOrder;
use App\Notification\Telegram;
use Mockery\MockInterface;
use Tests\Feature\CustomerCanCreateOrderTest;

trait TriggerCustomerCreateAction
{

    public function orderAttributes(mixed $overwrites)
    {
        $attributes = OnlineOrder::factory()->make()->toArray();
        $attributes['address_id'] = $this->address->id;
        unset($attributes['order_id']);
//        dd($attributes);
        return array_merge($attributes, $overwrites);
    }

    public function createOrderWithMock($overwrites = [])
    {
        return $this->createOrder($overwrites, true);
    }

    public function createOrder($overwrites = [], $needMock = false)
    {
        if ($needMock) {
            $this->mock(Telegram::class, function (MockInterface $mock) {
                $mock->shouldReceive('sendOrderCreatedNotification')->once()->andReturn(true);
            });
        }

        return $this->signIn($this->user)->postJson($this->endpoint,
            $this->orderAttributes($overwrites)
        );
    }
}
