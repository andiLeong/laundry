<?php

namespace Tests;

use App\Models\Order;

trait OrderCanBeCreated
{
    protected string $endpoint = 'api/admin/order';


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
}
