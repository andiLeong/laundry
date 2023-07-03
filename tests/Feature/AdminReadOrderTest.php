<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReadOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/order';
    private string $phone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phone = '09050887900';
    }

    /** @test */
    public function it_can_get_all_orders(): void
    {
        $orders = Order::factory(2)->create();
        $ids = $this->fetch()->assertOk()->collect()->pluck('id');

        $this->assertTrue($ids->contains($orders[0]->id));
        $this->assertTrue($ids->contains($orders[1]->id));
    }

    /** @test */
    public function employee_can_only_view_order_that_they_created(): void
    {
        $employee = User::factory()->create(['type' => UserType::employee->value]);
        $employee2 = User::factory()->create(['type' => UserType::employee->value]);
        $employeeOrder = Order::factory()->create(['creator_id' => $employee->id]);
        $employee2Orders = Order::factory(2)->create(['creator_id' => $employee2->id]);
        $ids = $this->fetchOrderIds([], $employee);

        $this->assertTrue($ids->contains($employeeOrder->id));
        $this->assertFalse($ids->contains($employee2Orders[0]->id));
        $this->assertFalse($ids->contains($employee2Orders[1]->id));
    }

    /** @test */
    public function admin_can_view_all_the_order(): void
    {
        $employee = User::factory()->create(['type' => UserType::employee->value]);
        $employee2 = User::factory()->create(['type' => UserType::employee->value]);
        $employeeOrder = Order::factory()->create(['creator_id' => $employee->id]);
        $orders = Order::factory()->create(['creator_id' => $employee2->id]);
        $ids = $this->fetchOrderIds();

        $this->assertTrue($ids->contains($employeeOrder->id));
        $this->assertTrue($ids->contains($orders->id));
    }

    /** @test */
    public function list_of_attributes_gets_return(): void
    {
        $promotions = Promotion::factory(2)->create();
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $orders = Order::factory(2)->create(['user_id' => $user->id, 'service_id' => $service->id]);
        OrderPromotion::insertByPromotions($promotions, $orders[1]);
        $order = $this->fetch()->collect()->first();

        $this->assertEquals($user->phone, $order['user']['phone']);
        $this->assertEquals($user->first_name, $order['user']['first_name']);
        $this->assertEquals($service->name, $order['service']['name']);
        $this->assertEquals(2, $order['promotions_count']);
    }

    /** @test */
    public function order_is_order_by_latest_record(): void
    {
        $firstOrder = Order::factory()->create();
        $secondOrder = Order::factory()->create();
        $ids = $this->signInAsAdmin()->getJson($this->endpoint)
            ->collect();

        $this->assertEquals($ids[0]['id'], $secondOrder->id);
        $this->assertEquals($ids[1]['id'], $firstOrder->id);
    }

    /** @test */
    public function it_can_filter_by_user_phone_number(): void
    {
        $user = User::factory()->create(['phone' => $this->phone]);
        $orders = Order::factory(2)->create();
        $userOrder = Order::factory()->create(['user_id' => $user->id]);
        $ids = $this->fetchOrderIds(['phone' => $user->phone]);

        $this->assertTrue($ids->contains($userOrder->id));
        $this->assertFalse($ids->contains($orders[0]->id));
        $this->assertFalse($ids->contains($orders[1]->id));
    }

    /** @test */
    public function it_can_filter_by_user_id(): void
    {
        $user = User::factory()->create();
        $orders = Order::factory(2)->create();
        $userOrder = Order::factory()->create(['user_id' => $user->id]);
        $ids = $this->fetchOrderIds(['user_id' => $user->id]);

        $this->assertTrue($ids->contains($userOrder->id));
        $this->assertFalse($ids->contains($orders[0]->id));
        $this->assertFalse($ids->contains($orders[1]->id));
    }

    /** @test */
    public function it_can_filter_by_user_first_name(): void
    {
        $user = User::factory()->create(['first_name' => 'pasdsdsds']);
        $orders = Order::factory(2)->create();
        $userOrder = Order::factory()->create(['user_id' => $user->id]);
        $ids = $this->fetchOrderIds(['first_name' => $user->first_name]);

        $this->assertTrue($ids->contains($userOrder->id));
        $this->assertFalse($ids->contains($orders[0]->id));
        $this->assertFalse($ids->contains($orders[1]->id));
    }

    /** @test */
    public function it_can_filter_by_order_has_user_or_not(): void
    {
        $orders = Order::factory(2)->create();
        $nonUserOrder = Order::factory()->create(['user_id' => null]);
        $excludeUserIds = $this->fetchOrderIds(['exclude_user' => true]);
        $includeUserIds = $this->fetchOrderIds(['include_user' => true]);

        $this->assertTrue($excludeUserIds->contains($nonUserOrder->id));
        $this->assertFalse($excludeUserIds->contains($orders[0]->id));
        $this->assertFalse($excludeUserIds->contains($orders[1]->id));

        $this->assertTrue($includeUserIds->contains($orders[0]->id));
        $this->assertTrue($includeUserIds->contains($orders[1]->id));
        $this->assertFalse($includeUserIds->contains($nonUserOrder->id));
    }

    /** @test */
    public function only_admin_can_access()
    {
        $this->signIn()->getJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function only_login_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
    }

    protected function fetch($query = [], $as = null)
    {
        $query = http_build_query($query);
        return $this->signInAsAdmin($as)->getJson($this->endpoint . '?' . $query);
    }

    public function fetchOrderIds($query = [], $as = null)
    {
        return $this->fetch($query, $as)->collect()->pluck('id');
    }
}
