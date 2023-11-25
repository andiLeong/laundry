<?php

namespace Tests\Feature;

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
    public function it_read_its_creator_name(): void
    {
        $orderPaidRecord = OrderPaid::factory()->create(['creator_id' => $this->staff()->id]);
        $result = $this->fetch()->json('data')[0];

        $this->assertEquals($orderPaidRecord->creator->first_name, $result['creator']['first_name']);
    }

    protected function fetch($query = [], $as = null)
    {
        return $this->fetchAsAdmin($query, $as);
    }
}
