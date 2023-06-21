<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function an_order_should_contains_necessary_attributes(): void
    {
        $order = Order::factory([
            'user_id' => 1,
            'amount' => 100
        ])->create();

        $this->assertEquals(100, $order->amount);
        $this->assertEquals(1, $order->user_id);
    }

    /** @test */
    public function an_order_can_belongs_to_an_user()
    {
        $user = User::factory()->create();
        $order = Order::factory([
            'user_id' => $user->id,
        ])->create();

        $noUserOrder = Order::factory(['user_id' => null])->create();

        $this->assertEquals($user->id, $order->user->id);
        $this->assertNull($noUserOrder->user);
    }
}
