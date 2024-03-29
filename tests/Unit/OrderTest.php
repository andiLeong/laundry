<?php

namespace Tests\Unit;

use App\Models\Enum\OrderPayment;
use App\Models\OnlineOrder;
use App\Models\Order;
use App\Models\OrderImage;
use App\Models\Service;
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
            'amount' => 100,
            'creator_id' => 2,
            'service_id' => 2,
        ])->create();

        $this->assertEquals(100, $order->amount);
        $this->assertEquals(1, $order->user_id);
        $this->assertEquals(2, $order->creator_id);
        $this->assertEquals(2, $order->service_id);
        $this->assertEquals(OrderPayment::CASH->toLower(), $order->payment);
        $this->assertTrue($order->paid);
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

    /** @test */
    public function it_belongs_to_an_creator()
    {
        $user = User::factory()->create();
        $order = Order::factory([
            'creator_id' => $user->id,
        ])->create();

        $this->assertEquals($user->id, $order->fresh()->creator->id);
    }

    /** @test */
    public function it_belongs_to_an_service()
    {
        $service = Service::factory()->create();
        $order = Order::factory([
            'service_id' => $service->id,
        ])->create();

        $this->assertEquals($service->id, $order->fresh()->service->id);
    }

    /** @test */
    public function it_can_has_an_online_order()
    {
        $order = Order::factory()->create();
        $onlineOrder = OnlineOrder::factory()->create([
            'order_id' => $order->id
        ]);

        $this->assertEquals($order->onlineOrder->id, $onlineOrder->id);
    }

    /** @test */
    public function it_can_has_many_images()
    {
        $order = Order::factory()->create();
        $image = OrderImage::factory()->create(['order_id' => $order->id]);
        $image2 = OrderImage::factory()->create(['order_id' => $order->id]);

        $this->assertEquals($order->images[0]->id, $image->id);
        $this->assertEquals($order->images[1]->id, $image2->id);
    }
}
