<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadOrder extends TestCase
{
    use LazilyRefreshDatabase;

    protected $endpoint = '/api/order';

    /** @test */
    public function unauthenticated_user_gets_401(): void
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
    }

    /** @test */
    public function it_can_only_gets_auth_user_order_other_user_order_cant_retrieve(): void
    {
        $user = $this->customer();
        $user2 = $this->customer();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $order2 = Order::factory(2)->create(['user_id' => $user2->id]);
        $response = $this->signIn($user)->getJson($this->endpoint)->collect('data')->pluck('id')->toArray();

        $this->assertTrue(in_array($order->id, $response));
        $this->assertFalse(in_array($order2[0]->id, $response));
        $this->assertFalse(in_array($order2[1]->id, $response));
    }

    /** @test */
    public function it_can_get_order_with_appropriate_columns(): void
    {
        $columns = [
            'id',
            'service',
            'amount',
            'product_amount',
            'total_amount',
            'paid',
            'payment',
            'created_at',
            'service_id'
        ];
        $user = $this->customer();
        $order = Order::factory()->create(['user_id' => $user->id]);
        $firstOrder = $this->signIn($user)->getJson($this->endpoint)->json('data')[0];
        $this->assertColumnsSame($columns, array_keys($firstOrder));

        $this->assertEquals($order->id, $firstOrder['id']);
        $this->assertEquals($order->amount, $firstOrder['amount']);
        $this->assertEquals($order->amount, $firstOrder['total_amount']);
        $this->assertEquals($order->product_amount, $firstOrder['product_amount']);
        $this->assertEquals($order->paid, $firstOrder['paid']);
        $this->assertEquals($order->payment, $firstOrder['payment']);
        $this->assertEquals($order->service->name, $firstOrder['service']['name']);
        $this->assertEquals($order->created_at->toJson(), $firstOrder['created_at']);
    }
}
