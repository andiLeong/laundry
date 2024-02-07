<?php


use App\Models\Enum\OrderPayment;
use App\Models\Enum\UserType;
use App\Models\Order;
use App\Models\OrderImage;
use App\Models\OrderPromotion;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminShowOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/order/';

    /** @test */
    public function employee_can_only_view_order_that_within_seven_days(): void
    {
        $employee = $this->staff();
        $nineDay = Order::factory()->create(['created_at' => today()->subDays(8)]);
        $eightDay = Order::factory()->create(['created_at' => today()->subDays(9)]);
        $sevenDay = Order::factory()->create(['created_at' => today()->subDays(7)]);
        $today = Order::factory()->create();

        $this->fetch($today->id, $employee)->assertOk();
        $this->fetch($nineDay->id, $employee)->assertForbidden();
        $this->fetch($eightDay->id, $employee)->assertForbidden();
        $this->fetch($sevenDay->id, $employee)->assertForbidden();
    }

    /** @test */
    public function admin_can_view_all_the_order_no_matter_who_create(): void
    {
        $admin = User::factory()->create(['type' => UserType::ADMIN->value]);
        $employee = User::factory()->create(['type' => UserType::EMPLOYEE->value]);
        $employee2 = User::factory()->create(['type' => UserType::EMPLOYEE->value]);
        $employeeOrder = Order::factory()->create(['creator_id' => $employee->id]);
        $employeeOrder2 = Order::factory()->create(['creator_id' => $employee2->id]);

        $this->fetch($employeeOrder2->id, $admin)->assertOk();
        $this->fetch($employeeOrder->id, $admin)->assertOk();
    }

    /** @test */
    public function list_of_attributes_gets_return(): void
    {
        $promotions = Promotion::factory(2)->create();
        $user = User::factory()->create();
        $service = Service::factory()->create();
        $order = Order::factory()->create(['user_id' => $user->id, 'service_id' => $service->id]);
        $orderImage = OrderImage::factory()->create(['order_id' => $order->id,]);
        OrderPromotion::insertByPromotions($promotions, $order);
        $order = $this->fetch($order->id);
        dump($order->json());

        $orderPromotion = array_column($order['promotions'], 'name');
        $this->assertEquals($user->phone, $order['user']['phone']);
        $this->assertEquals($user->first_name, $order['user']['first_name']);
        $this->assertEquals($service->name, $order['service']['name']);
        $this->assertEquals($orderImage->path, $order['images'][0]['path']);
        $this->assertEquals($orderImage->creator->first_name, $order['images'][0]['creator']['first_name']);
        $this->assertArrayHasKey('gcash', $order->json());

        $this->assertTrue(in_array($promotions[1]->name, $orderPromotion));
        $this->assertTrue(in_array($promotions[0]->name, $orderPromotion));
    }

    /** @test */
    public function if_order_is_gcash_payment_it_can_get_gcash_reference_number(): void
    {
        $order = Order::factory()->create(['payment' => OrderPayment::GCASH->value]);
        \App\Models\GcashOrder::create([
            'order_id' => $order->id,
            'reference_number' => 'xxx',
        ]);
        $order = $this->fetch($order->id);
        $this->assertEquals('xxx', $order['gcash']['reference_number']);
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
        return $this->fetchAsAdmin([], $as, $this->endpoint . $id);
    }
}
