<?php


use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminShowOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/order/';

    /** @test */
    public function employee_can_only_view_order_that_they_created(): void
    {
        $employee = User::factory()->create(['type' => UserType::employee->value]);
        $employee2 = User::factory()->create(['type' => UserType::employee->value]);
        $employeeOrder = Order::factory()->create(['creator_id' => $employee->id]);
        $employeeOrder2 = Order::factory()->create(['creator_id' => $employee2->id]);

        $this->fetch($employeeOrder->id,$employee)->assertOk();
        $this->fetch($employeeOrder->id,$employee2)->assertForbidden();

        $this->fetch($employeeOrder2->id,$employee)->assertForbidden();
        $this->fetch($employeeOrder2->id,$employee2)->assertOk();
    }

    /** @test */
    public function admin_can_view_all_the_order_no_matter_who_create(): void
    {
        $admin = User::factory()->create(['type' => UserType::admin->value]);
        $employee = User::factory()->create(['type' => UserType::employee->value]);
        $employee2 = User::factory()->create(['type' => UserType::employee->value]);
        $employeeOrder = Order::factory()->create(['creator_id' => $employee->id]);
        $employeeOrder2 = Order::factory()->create(['creator_id' => $employee2->id]);

        $this->fetch($employeeOrder2->id,$admin)->assertOk();
        $this->fetch($employeeOrder->id,$admin)->assertOk();
    }

    /** @test */
    public function list_of_attributes_gets_return(): void
    {
        $promotions = Promotion::factory(2)->create();
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'service_id' => $service->id]);
        OrderPromotion::insertByPromotions($promotions, $order);
        $order = $this->fetch($order->id);

        $orderPromotion = array_column($order['promotions'], 'name');
        $this->assertEquals($user->phone, $order['user']['phone']);
        $this->assertEquals($user->first_name, $order['user']['first_name']);
        $this->assertEquals($service->name, $order['service']['name']);

        $this->assertTrue(in_array($promotions[1]->name, $orderPromotion));
        $this->assertTrue(in_array($promotions[0]->name, $orderPromotion));
    }

    /** @test */
    public function only_admin_or_employee_can_access()
    {
        $order = Order::factory()->create();
        $this->signIn()->getJson($this->endpoint . $order->id)->assertForbidden();
    }

    /** @test */
    public function only_login_user_can_access()
    {
        $this->getJson($this->endpoint . 9999)->assertUnauthorized();
    }

    protected function fetch($id, $as = null)
    {
        return $this->signInAsAdmin($as)->getJson($this->endpoint . $id);
    }
}
