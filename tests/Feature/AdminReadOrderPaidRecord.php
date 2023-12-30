<?php

namespace Tests\Feature;

use App\Models\Enum\OrderPayment;
use App\Models\Order;
use App\Models\OrderPaid;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReadOrderPaidRecord extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/order-paid-record';

    /** @test */
    public function only_admin_or_employee_can_access()
    {
        $this->signIn()->getJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function it_can_read_order_paid_record(): void
    {
        $orderPaidRecord = OrderPaid::factory()->create(['id' => 1000]);
        $response = $this->fetch();
        $response->assertStatus(200);
        $this->assertEquals($orderPaidRecord->id, $response->json('data')[0]['id']);
    }

    /** @test */
    public function it_default_return_today_record(): void
    {
        $orderPaidRecord = OrderPaid::factory()->create(['id' => 1000]);
        $yesterdayOrderPaidRecord = OrderPaid::factory()->create(['id' => 999, 'created_at' => now()->subDay()]);
        $result = $this->fetch()->collect('data')->pluck('id')->toArray();
        $this->assertTrue(in_array($orderPaidRecord->id, $result));
        $this->assertFalse(in_array($yesterdayOrderPaidRecord->id, $result));
    }

    /** @test */
    public function it_can_filter_dy_datetime(): void
    {
        $order = OrderPaid::factory()->create();
        $yesterdayOrderPaidRecord = OrderPaid::factory()->create(['created_at' => today()->subDay()]);
        $yesterdayIds = $this->fetch([
            'start' => today()->subDay()->toDateTimeString(),
            'end' => today()->subDay()->addHours(10)->toDateTimeString()
        ])->collect('data')->pluck('id');

        $this->assertTrue($yesterdayIds->contains($yesterdayOrderPaidRecord->id));
        $this->assertFalse($yesterdayIds->contains($order->id));
    }


    /** @test */
    public function it_can_filter_dy_payment(): void
    {
        $cashOrder = Order::factory()->create(['payment' => OrderPayment::CASH->value]);
        $gcashOrder = Order::factory()->create(['payment' => OrderPayment::GCASH->value]);
        $cashOrderPaidRecord = OrderPaid::factory()->create(['order_id' => $cashOrder->id]);
        $gcashOrderPaidRecord = OrderPaid::factory()->create(['order_id' => $gcashOrder->id]);
        $cashResult = $this->fetch([
            'payment' => OrderPayment::CASH->value,
        ])->collect('data')->pluck('id');
        $gcashResult = $this->fetch([
            'payment' => OrderPayment::GCASH->value,
        ])->collect('data')->pluck('id');

        $this->assertTrue($cashResult->contains($cashOrderPaidRecord->id));
        $this->assertFalse($cashResult->contains($gcashOrderPaidRecord->id));

        $this->assertTrue($gcashResult->contains($gcashOrderPaidRecord->id));
        $this->assertFalse($gcashResult->contains($cashOrderPaidRecord->id));
    }

    /** @test */
    public function it_read_its_creator_name(): void
    {
        $orderPaidRecord = OrderPaid::factory()->create(['creator_id' => $this->staff()->id]);
        $result = $this->fetch()->json('data')[0];

        $this->assertEquals($orderPaidRecord->creator->first_name, $result['creator']['first_name']);
    }

    /** @test */
    public function it_read_its_order_detail(): void
    {
        $order = Order::factory()->create(['paid' => true]);
        $orderPaidRecord = OrderPaid::factory()->create(['order_id' => $order]);
        $result = $this->fetch()->json('data')[0];

        $this->assertEquals($orderPaidRecord->order->description, $result['order']['description']);
        $this->assertEquals($orderPaidRecord->order->payment, $result['order']['payment']);
    }

    /** @test */
    public function it_read_its_total_amount(): void
    {
        $orderPaidRecord = OrderPaid::factory()->create(['amount' => 300]);
        $orderPaidRecord2 = OrderPaid::factory()->create(['amount' => 250]);
        $total = $this->fetch()->json('total');

        $this->assertEquals($orderPaidRecord->amount + $orderPaidRecord2->amount, $total);
    }

    protected function fetch($query = [], $as = null)
    {
        return $this->fetchAsAdmin($query, $as);
    }
}
