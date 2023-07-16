<?php


use App\Models\Order;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\OrderCanBeCreated;
use Tests\TestCase;
use Tests\UserCanBeVerified;
use Tests\Validate;

class AdminCreateOrderTest extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderCanBeCreated;
    use UserCanBeVerified;

    protected string $phone = '09060785699';

    /** @test */
    public function it_can_create_order(): void
    {
        $this->assertDatabaseCount('orders', 0);
        $response = $this->createOrder();
        $this->assertDatabaseCount('orders', 1);
        $response->assertSuccessful();
    }

    /** @test */
    public function only_admin_and_employee_can_create_order()
    {
        $customer = User::factory()->create();
        $this->actingAs($customer);

        $customer2 = User::factory()->create();
        $response = $this->postJson($this->endpoint, [
            'user_id' => $customer2->id,
            'amount' => 200,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function only_login_user_can_create_order()
    {
        $this->postJson($this->endpoint, [
            'user_id' => 100,
            'amount' => 200,
        ])->assertUnauthorized();
    }

    /** @test */
    public function amount_must_be_valid()
    {
        $name = 'amount';
        $rule = ['nullable', 'decimal'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function user_id_must_be_valid()
    {
        $name = 'user_id';
        $this->createOrder([$name => 9988])->assertJsonValidationErrorFor($name);
        $this->createOrder([$name => null])->assertJsonMissingValidationErrors($name);
    }

    /** @test */
    public function unverified_user_cant_be_create_order()
    {
        $this->setUnverifiedUser();
        $response = $this->createOrder(['user_id' => $this->user->id]);
        $this->assertValidateMessage('user is invalid', $response, 'user_id');
    }

    /** @test */
    public function service_id_must_valid()
    {
        $name = 'service_id';
        $this->createOrder([$name => 9988])->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function it_use_service_price_as_amount_if_service_id_is_provided()
    {
        $order = Order::first();
        $this->assertNull($order);
        $service = Service::factory()->create([
            'price' => 200
        ]);

        $this->createOrder([
            'service_id' => $service->id,
            'amount' => 100,
        ]);

        $order = Order::first();
        $this->assertEquals(100, $order->amount);
    }

    /** @test */
    public function its_amount_default_to_service_price()
    {
        $order = Order::first();
        $this->assertNull($order);

        $service = Service::factory()->create(['price' => 201]);
        $this->createOrder([
            'service_id' => $service->id,
            'amount' => null,
        ]);

        $order = Order::first();
        $this->assertEquals(201, $order->amount);
    }

    private function orderAttributes(mixed $overwrites)
    {
        $attributes = Order::factory()->make()->toArray();
        return array_merge($attributes, $overwrites);
    }
}
