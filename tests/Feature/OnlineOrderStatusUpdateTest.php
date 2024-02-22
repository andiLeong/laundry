<?php

namespace Tests\Feature;

use App\Models\Enum\OnlineOrderStatus;
use App\Models\Enum\OrderType;
use App\Models\OnlineOrder;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\AdminAuthorization;
use Tests\OrderImageCanBeValidated;
use Tests\TestCase;
use Tests\Validate;

class OnlineOrderStatusUpdateTest extends TestCase
{
    use LazilyRefreshDatabase;
    use AdminAuthorization;
    use OrderImageCanBeValidated;

    protected $endpoint = '/api/admin/online-order';

    protected function setUp(): void
    {
        parent::setUp();
        $this->method = 'patchJson';
        $this->order = Order::factory()->create([
            'type' => OrderType::ONLINE->value,
        ]);
        $this->onlineOrder = OnlineOrder::factory()->create([
            'order_id' => $this->order->id,
            'pickup_at' => null,
            'deliver_at' => null
        ]);
        $this->endpoint = $this->endpoint . '/' . $this->onlineOrder->id;
        $this->imageArraySize = 2;
        $this->imageCreation = 'update';
    }

    /** @test */
    public function type_must_correct()
    {
        $name = 'type';
        $rule = ['required', 'in:pickup,delivery'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->update($payload)
        );
    }

    /** @test */
    public function image_is_nullable()
    {
        $name = 'image';
        $rule = ['nullable', 'array'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->update($payload)
        );
    }

    /** @test */
    public function it_gets_404_if_order_id_not_exists(): void
    {
        $this->signInAsAdmin()
            ->patchJson('/api/admin/online-order/999999', ['type' => 'pickup'])
            ->assertNotFound();
    }

    /** @test */
    public function if_online_order_is_delivered_it_cant_update_status(): void
    {
        $this->onlineOrder->update(['status' => OnlineOrderStatus::PICKUP->value]);
        $message = $this->update(['type' => 'pickup'])->assertStatus(400)->json('message');
        $this->assertEquals($message, 'Order must from pending pickup to picked up');
    }

    /** @test */
    public function it_gets_400_if_online_order_status_is_not_pending_when_update_to_pickup(): void
    {
        $this->onlineOrder->update(['status' => OnlineOrderStatus::DELIVERED->value]);
        $this->update(['type' => 'pickup'])->assertStatus(400);
        $this->update(['type' => 'delivery'])->assertStatus(400);
    }

    /** @test */
    public function it_gets_400_if_online_order_status_is_not_pickup_when_update_to_delivered(): void
    {
        $this->onlineOrder->update(['status' => OnlineOrderStatus::PENDING_PICKUP->value]);
        $message = $this->update(['type' => 'delivery'])->assertStatus(400)->json('message');
        $this->assertEquals($message, 'Order need to pickup first, then set delivery status');
    }

    /** @test */
    public function pending_online_order_can_be_updated_to_pickup(): void
    {
        $this->assertNull($this->onlineOrder->pickup_at);
        $this->update(['type' => 'pickup'])
            ->assertStatus(200);

        $this->assertEquals(OnlineOrderStatus::PICKUP->toLower(), $this->onlineOrder->fresh()->status);
        $this->assertEquals(now()->toDateTimeString(), $this->onlineOrder->fresh()->pickup_at);
    }

    /** @test */
    public function picked_up_online_order_can_be_updated_to_deliver(): void
    {
        $this->onlineOrder->update(['status' => OnlineOrderStatus::PICKUP->value]);

        $this->assertNull($this->onlineOrder->fresh()->deliver_at);
        $this->update(['type' => 'delivery'])
            ->assertStatus(200);

        $this->assertEquals(OnlineOrderStatus::DELIVERED->toLower(), $this->onlineOrder->fresh()->status);
        $this->assertEquals(now()->toDateTimeString(), $this->onlineOrder->fresh()->deliver_at);
    }

    public function update($arr = [])
    {
        return $this->signInAsAdmin()->patchJson($this->endpoint, $arr);
    }
}
