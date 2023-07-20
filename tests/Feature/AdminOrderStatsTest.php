<?php

namespace Tests\Feature;

use App\Models\Order;
use Carbon\CarbonPeriod;
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

        $response = $this->fetch();

        $this->assertEquals(count($orders), $response['order_count']);
        $this->assertEquals(count($orders) * 100, $response['order_total_amount']);
    }

    /** @test */
    public function it_can_get_monthly_order_count_and_total_order_amount_count(): void
    {
        $orders = Order::factory(10)->create(['amount' => 100, 'created_at' => now()->subDays()]);
        Order::factory()->create(['created_at' => now()->subMonths(1)]);
        Order::factory()->create(['created_at' => now()->subMonths(2)]);

        $response = $this->fetch(['timeframe' => 'monthly']);

        $this->assertEquals(count($orders), $response['order_count']);
        $this->assertEquals(count($orders) * 100, $response['order_total_amount']);
    }

    /** @test */
    public function it_can_get_weekly_order_count_and_total_order_amount_count(): void
    {
        $today = Order::factory()->create(['amount' => 100]);
        $tomorrow = Order::factory()->create(['amount' => 100, 'created_at' => now()->addDay()]);
        $lastDayOfWeek = Order::factory()->create(['amount' => 100, 'created_at' => now()->addDays(6)]);
        $lastMinuts = Order::factory()->create(['amount' => 100, 'created_at' => now()->addDays(7)->subMinutes(90)]);

        $yesterday = Order::factory()->create(['amount' => 100, 'created_at' => now()->subDays()]);
        $nextWeek = Order::factory()->create(['amount' => 100, 'created_at' => now()->addDays(7)]);

        $response = $this->fetch(['timeframe' => 'weekly']);
        $this->assertEquals(3, $response['order_count']);
        $this->assertEquals(3 * 100, $response['order_total_amount']);
    }

    /** @test */
    public function it_can_see_order_count_and_total_amount_group_by_days_in_pass_x_days(): void
    {
        $this->withoutExceptionHandling();
        $days = 5;
        $today = Order::factory()->create(['amount' => 100, 'created_at' => today()->addHours()]);
        $today2 = Order::factory()->create(['amount' => 200, 'created_at' => today()->addHours(2)]);
        $yesterday = Order::factory()->create(['amount' => 90, 'created_at' => now()->subDays()]);

        $tomorrow = Order::factory()->create(['amount' => 100, 'created_at' => now()->addDay()]);
        $lastDayOfWeek = Order::factory()->create(['amount' => 100, 'created_at' => now()->subDays(31)]);

        $dates = array_map(
            fn($dt) => $dt->format('Y-m-d'),
            CarbonPeriod::create(today()->subDays($days - 1), today())->toArray()
        );

        $response = $this->fetch(['group_by_days' => $days])->keyBy('dt');

        $this->assertEquals($dates, $response->pluck('dt')->values()->all());
        $this->assertEquals(
            $today->amount + $today2->amount,
            $response[today()->format('Y-m-d')]['order_total_amount']
        );
        $this->assertEquals(
            $yesterday->amount,
            $response[today()->subDays()->format('Y-m-d')]['order_total_amount']
        );

        $this->assertEquals(2, $response[today()->format('Y-m-d')]['order_count'] );

        $this->assertArrayNotHasKey($tomorrow->created_at->format('Y-m'), $response);
        $this->assertArrayNotHasKey($lastDayOfWeek->created_at->format('Y-m'), $response);
    }

    /** @test */
    public function it_can_see_order_count_and_total_amount_group_by_months_in_pass_x_months(): void
    {
        $month = 6;
        $currentMonth = Order::factory(2)->create(['amount' => 100]);
        $lastMonth = Order::factory()->create(['amount' => 80, 'created_at' => today()->subMonths()]);
        $twoMonthsAgo = Order::factory()->create(['amount' => 90, 'created_at' => today()->subMonths(2)]);

        $lastYear = Order::factory()->create(['amount' => 100, 'created_at' => today()->subYears()]);
        $nextMonth = Order::factory()->create(['amount' => 100, 'created_at' => today()->addMonths()]);

        $dates = array_map(
            fn($dt) => $dt->format('Y-m'),
            CarbonPeriod::create(now()->startOfMonth()->subMonths($month - 1), '1 month', now()->startOfMonth())->toArray()
        );
        $response = $this->fetch(['group_by_months' => $month])->keyBy('dt');

        $this->assertEquals($dates, $response->pluck('dt')->values()->all());
        $this->assertEquals(
            $currentMonth->sum('amount'),
            $response[today()->format('Y-m')]['order_total_amount']
        );
        $this->assertEquals(
            $lastMonth->amount,
            $response[today()->subMonths()->format('Y-m')]['order_total_amount']
        );

        $this->assertEquals(
            $twoMonthsAgo->amount,
            $response[today()->subMonths(2)->format('Y-m')]['order_total_amount']
        );
        $this->assertEquals(2, $response[today()->format('Y-m')]['order_count'] );

        $this->assertArrayNotHasKey($lastYear->created_at->format('Y-m'), $response);
        $this->assertArrayNotHasKey($nextMonth->created_at->format('Y-m'), $response);
    }

    /** @test */
    public function only_authenticated_admin_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
        $this->be($this->staff())->getJson($this->endpoint)->assertForbidden();
    }

    public function fetch($query = [])
    {
        $query = '?' . http_build_query($query);
        return $this
            ->signInAsAdmin()
            ->getJson($this->endpoint . $query)
            ->assertStatus(200)
            ->collect();
    }
}
