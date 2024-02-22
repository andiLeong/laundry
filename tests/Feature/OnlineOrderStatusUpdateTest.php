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
    public function pending_online_order_can_be_updated_to_pickup(): void
    {
        $this->assertNull($this->onlineOrder->pickup_at);
        $this->update(['type' => 'pickup'])
            ->assertStatus(200);

        $this->assertEquals(OnlineOrderStatus::PICKUP->toLower(),$this->onlineOrder->fresh()->status);
        $this->assertEquals(now()->toDateTimeString(),$this->onlineOrder->fresh()->pickup_at);
    }

    /** @test */
    public function picked_up_online_order_can_be_updated_to_deliver(): void
    {
        $this->onlineOrder->update(['status' => OnlineOrderStatus::PICKUP->value]);

        $this->assertNull($this->onlineOrder->fresh()->deliver_at);
        $this->update(['type' => 'delivery'])
            ->assertStatus(200);

        $this->assertEquals(OnlineOrderStatus::DELIVERED->toLower(),$this->onlineOrder->fresh()->status);
        $this->assertEquals(now()->toDateTimeString(),$this->onlineOrder->fresh()->deliver_at);
    }

    public function update($arr = [])
    {
        return $this->signInAsAdmin()->patchJson($this->endpoint,$arr);
    }
}
