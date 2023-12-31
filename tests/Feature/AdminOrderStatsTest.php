<?php

namespace Tests\Feature;

use App\Models\Expense;
use App\Models\Order;
use Carbon\CarbonPeriod;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
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
        Carbon::setTestNow('2023-07-10');
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
        Carbon::setTestNow(Carbon::parse('2023-07-17')); //set to monday
        $today = Order::factory()->create(['amount' => 100]);
        $tomorrow = Order::factory()->create(['amount' => 100, 'created_at' => today()->addDay()]);
        $sunday = Order::factory()->create(['amount' => 100, 'created_at' => today()->addDays(6)]);
        $nextMonday = Order::factory()->create(['amount' => 100, 'created_at' => today()->addDays(7)]);

        $yesterday = Order::factory()->create(['amount' => 100, 'created_at' => today()->subDays()]);

        $response = $this->fetch(['timeframe' => 'weekly']);
        $this->assertEquals(3, $response['order_count']);
        $this->assertEquals(3 * 100, $response['order_total_amount']);
    }

    /** @test */
    public function it_can_see_order_count_and_total_amount_group_by_days_in_pass_x_days(): void
    {
        $days = 5;
        $today = Order::factory()->create(['total_amount' => 100, 'created_at' => today()->addHours()]);
        $today2 = Order::factory()->create(['total_amount' => 200, 'created_at' => today()->addHours(2)]);
        $yesterday = Order::factory()->create(['total_amount' => 90, 'created_at' => now()->subDays()]);

        $tomorrow = Order::factory()->create(['total_amount' => 100, 'created_at' => now()->addDay()]);
        $lastDayOfWeek = Order::factory()->create(['total_amount' => 100, 'created_at' => now()->subDays(31)]);

        $dates = array_map(
            fn($dt) => $dt->format('Y-m-d'),
            CarbonPeriod::create(today()->subDays($days - 1), today())->toArray()
        );

        $response = collect($this->fetch(['group_by_days' => $days])->get('data'))->keyBy('dt');

        $this->assertEquals($dates, $response->pluck('dt')->values()->all());
        $this->assertEquals(
            $today->total_amount + $today2->total_amount,
            $response[today()->format('Y-m-d')]['order_total_amount']
        );
        $this->assertEquals(
            $yesterday->total_amount,
            $response[today()->subDays()->format('Y-m-d')]['order_total_amount']
        );

        $this->assertEquals(2, $response[today()->format('Y-m-d')]['order_count']);

        $this->assertArrayNotHasKey($tomorrow->created_at->format('Y-m'), $response);
        $this->assertArrayNotHasKey($lastDayOfWeek->created_at->format('Y-m'), $response);
    }

    /** @test */
    public function it_can_see_avg_order_count_and_avg_amount_group_by_days_in_pass_x_days(): void
    {
        $days = 5;
        $today = Order::factory()->create(['total_amount' => 100, 'created_at' => today()->addHours()]);
        $today2 = Order::factory()->create(['total_amount' => 200, 'created_at' => today()->addHours(2)]);
        $yesterday = Order::factory()->create(['total_amount' => 89.56, 'created_at' => now()->subDays()]);

        $todayTotal = $today->total_amount + $today2->total_amount;
        $total = $todayTotal + $yesterday->total_amount;

        $response = $this->fetch(['group_by_days' => $days])->toArray();

        $this->assertArrayHasKey('order_avg', $response);
        $this->assertEquals($response['order_avg'], round(3 / $days, 2));
        $this->assertArrayHasKey('amount_avg', $response);
        $this->assertEquals($response['amount_avg'], round($total / $days, 2));
    }

    /** @test */
    public function it_can_see_order_count_and_total_amount_group_by_months_in_pass_x_months(): void
    {
        Carbon::setTestNow('2023-07-10');
        $month = 6;
        $currentMonth = Order::factory(2)->create(['total_amount' => 100]);
        $lastMonth = Order::factory()->create(['total_amount' => 80, 'created_at' => today()->subMonths()]);
        $twoMonthsAgo = Order::factory()->create(['total_amount' => 90, 'created_at' => today()->subMonths(2)]);

        $lastYear = Order::factory()->create(['total_amount' => 100, 'created_at' => today()->subYears()]);
        $nextMonth = Order::factory()->create(['total_amount' => 100, 'created_at' => today()->addMonths()]);

        $dates = array_map(
            fn($dt) => $dt->format('Y-m'),
            CarbonPeriod::create(now()->startOfMonth()->subMonths($month - 1), '1 month', now()->startOfMonth())->toArray()
        );
        $response = collect($this->fetch(['group_by_months' => $month])->get('data'))->keyBy('dt');

        $this->assertEquals($dates, $response->pluck('dt')->values()->all());
        $this->assertEquals(
            $currentMonth->sum('total_amount'),
            $response[today()->format('Y-m')]['order_total_amount']
        );
        $this->assertEquals(
            $lastMonth->total_amount,
            $response[today()->subMonths()->format('Y-m')]['order_total_amount']
        );

        $this->assertEquals(
            $twoMonthsAgo->total_amount,
            $response[today()->subMonths(2)->format('Y-m')]['order_total_amount']
        );
        $this->assertEquals(2, $response[today()->format('Y-m')]['order_count']);

        $this->assertArrayNotHasKey($lastYear->created_at->format('Y-m'), $response);
        $this->assertArrayNotHasKey($nextMonth->created_at->format('Y-m'), $response);
    }

    /** @test */
    public function it_can_see_avg_order_count_and_avg_amount_group_by_days_in_pass_x_months(): void
    {
        Carbon::setTestNow('2023-07-10');
        $month = 6;
        $currentMonth = Order::factory(2)->create(['total_amount' => 100]);
        $lastMonth = Order::factory()->create(['total_amount' => 80, 'created_at' => today()->subMonths()]);
        $twoMonthsAgo = Order::factory()->create(['total_amount' => 90, 'created_at' => today()->subMonths(2)]);
        $total = $currentMonth->sum('total_amount') + $lastMonth->total_amount + $twoMonthsAgo->total_amount;


        $response = $this->fetch(['group_by_months' => $month])->all();

        $this->assertArrayHasKey('order_avg', $response);
        $this->assertEquals($response['order_avg'], round(4 / $month, 2));
        $this->assertArrayHasKey('amount_avg', $response);
        $this->assertEquals($response['amount_avg'], round($total / $month, 2));
    }

    /** @test */
    public function it_can_see_margin_group_by_months_in_pass_x_months(): void
    {
        Carbon::setTestNow('2023-07-10');
        $month = 6;
        $currentMonth = Order::factory(2)->create(['total_amount' => 100]);
        $currentMonthExpense = Expense::factory()->create(['amount' => 50, 'created_at' => now()]);
        $lastMonth = Order::factory()->create(['total_amount' => 80, 'created_at' => today()->subMonths()]);
        $lastMonthExpense = Expense::factory()->create(['amount' => 800, 'created_at' => today()->subMonths()]);
        $currentMonthMargin = $currentMonth->sum('total_amount') - $currentMonthExpense->amount;
        $lastMonthMargin = $lastMonth->total_amount - $lastMonthExpense->amount;

        $dates = array_map(
            fn($dt) => $dt->format('Y-m'),
            CarbonPeriod::create(now()->startOfMonth()->subMonths($month - 1), '1 month', now()->startOfMonth())->toArray()
        );
        $response = $this->fetch(['margin_group_by_months' => $month])->keyBy('dt');

        $this->assertEquals($dates, $response->pluck('dt')->values()->all());
        $this->assertEquals(
            $currentMonthMargin,
            $response[today()->format('Y-m')]['margin']
        );
        $this->assertEquals(
            $lastMonthMargin,
            $response[today()->subMonths()->format('Y-m')]['margin']
        );

        $this->assertEquals(0, $response[today()->subMonths(2)->format('Y-m')]['margin']);
    }

    /** @test */
    public function only_authenticated_admin_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
        $this->be($this->staff())->getJson($this->endpoint)->assertForbidden();
    }

    public function fetch($query = [])
    {
        return $this->fetchAsAdmin($query)->assertStatus(200)->collect();
    }
}
