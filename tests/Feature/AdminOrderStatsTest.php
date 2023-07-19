<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminOrderStatsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $endpoint = '/api/admin/order-stats';

    /** @test */
    public function it_can_get_today_order_count_and_total_order_amount_count(): void
    {
        $orders = Order::factory(10)->create(['amount' => 100]);
        Order::factory()->create(['created_at' => now()->subDays(10)]);
        Order::factory()->create(['created_at' => now()->subDays(200)]);

        $response = $this->signInAsAdmin()->getJson($this->endpoint)->assertStatus(200)->collect();

        $this->assertEquals(count($orders), $response['order_count']);
        $this->assertEquals(count($orders) * 100, $response['order_total_amount']);
    }

    /** @test */
    public function it_can_get_monthly_order_count_and_total_order_amount_count(): void
    {
        $orders = Order::factory(10)->create(['amount' => 100,'created_at' => now()->subDays()]);
        Order::factory()->create(['created_at' => now()->subMonths(1)]);
        Order::factory()->create(['created_at' => now()->subMonths(2)]);

        $response = $this
            ->signInAsAdmin()
            ->getJson($this->endpoint . '?timeframe=monthly')
            ->assertStatus(200)
            ->collect();

        $this->assertEquals(count($orders), $response['order_count']);
        $this->assertEquals(count($orders) * 100, $response['order_total_amount']);
    }

    /** @test */
    public function it_can_get_weekly_order_count_and_total_order_amount_count(): void
    {
        $today = Order::factory()->create(['amount' => 100]);
        $tomorrow = Order::factory()->create(['amount' => 100,'created_at' => now()->addDay()]);
        $lastDayOfWeek = Order::factory()->create(['amount' => 100,'created_at' => now()->addDays(6)]);
        $lastMinuts = Order::factory()->create(['amount' => 100,'created_at' => now()->addDays(7)->subMinutes(90)]);

        $yesterday = Order::factory()->create(['amount' => 100,'created_at' => now()->subDays()]);
        $nextWeek = Order::factory()->create(['amount' => 100,'created_at' => now()->addDays(7)]);

        $response = $this
            ->signInAsAdmin()
            ->getJson($this->endpoint . '?timeframe=weekly')
            ->assertStatus(200)
            ->collect();

        $this->assertEquals(3, $response['order_count']);
        $this->assertEquals(3 * 100, $response['order_total_amount']);
    }
}
