<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class AdminCreateOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/order';

    /** @test */
    public function it_can_create_order(): void
    {
        $this->markTestSkipped();
        $this->assertDatabaseCount('orders', 0);
        $response = $this->createOrder();
        $this->assertDatabaseCount('orders', 1);
        $response->assertSuccessful();
    }

    /** @test */
    public function customer_it_self_cant_create_order()
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
    public function only_login_none_customer_can_create_order()
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
    public function isolated_must_be_valid()
    {
        $name = 'isolated';
        $this->signInAsAdmin()->postJson($this->endpoint, [$name => 2])->assertJsonValidationErrorFor($name);
        $this->signInAsAdmin()->postJson($this->endpoint, [$name => 3])->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function isolated_is_required_if_promotion_ids_is_present()
    {
        $promotion = Promotion::factory()->create();
        $this->signInAsAdmin()->postJson($this->endpoint, ['promotion_ids' => [$promotion->id]])->assertJsonValidationErrorFor('isolated');
    }

    /** @test */
    public function user_id_must_be_valid()
    {
        $name = 'user_id';
        $this->createOrder([$name => 9988])->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function service_id_must_valid()
    {
        $name = 'service_id';
        $this->createOrder([$name => 9988])->assertJsonValidationErrorFor($name);
    }

//    /** @test */
//    public function it_use_service_price_as_amount_if_service_id_is_provided()
//    {
//        $order = Order::first();
//        $this->assertNull($order);
//        $service = Service::factory()->create([
//            'price' => 200
//        ]);
//
//        $this->createOrder([
//            'service_id' => $service->id,
//            'amount' => 100,
//        ]);
//
//        $order = Order::first();
//        $this->assertEquals(100, $order->amount);
//    }
//
//    /** @test */
//    public function its_amount_default_to_service_price()
//    {
//        $order = Order::first();
//        $this->assertNull($order);
//
//        $service = Service::factory()->create(['price' => 201]);
//        $this->createOrder([
//            'service_id' => $service->id,
//            'amount' => null,
//        ]);
//
//        $order = Order::first();
//        $this->assertEquals(201, $order->amount);
//    }

    /** @test */
    public function non_existed_promotion_ids_validation()
    {
        $name = 'promotion_ids';
        $rule = ['nullable', 'array'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );

        $this->signInAsAdmin()->postJson($this->endpoint, [$name => [100, 101]])->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function expired_promotion_ids_validation()
    {
        $name = 'promotion_ids';
        $expiredPromotion = Promotion::factory()->create([
            'until' => now()->subDays()
        ]);

        $payload = $this->orderAttributes([
            $name => [$expiredPromotion->id],
        ]);
        $this->signInAsAdmin()->postJson($this->endpoint, $payload)->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function disabled_promotion_ids_validation()
    {
        $name = 'promotion_ids';
        $disabledPromotion = Promotion::factory()->create([
            'status' => false
        ]);

        $payload = $this->orderAttributes([
            $name => [$disabledPromotion->id],
        ]);
        $this->signInAsAdmin()->postJson($this->endpoint, $payload)->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function promotion_class_validation()
    {
//        $this->markTestSkipped();
        $name = 'promotion_ids';
        $classNotExistedPromotion = Promotion::factory()->create([
            'class' => 'app\\Foo\\Bar\\SignUp.php',
        ]);

        $payload = $this->orderAttributes([
            $name => [$classNotExistedPromotion->id],
        ]);
        $response = $this->signInAsAdmin()->postJson($this->endpoint, $payload);
        $response->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function if_isolated_flag_is_passed_then_the_promotion_must_be_isolated()
    {
        $name = 'promotion_ids';
        $nonIsolatedPromotion = Promotion::factory()->create([
            'isolated' => false
        ]);

        $payload = $this->orderAttributes([
            $name => [$nonIsolatedPromotion->id],
            'isolated' => 1
        ]);

        $this->signInAsAdmin()->postJson($this->endpoint, $payload)->assertJsonValidationErrorFor($name);
    }

    public function createOrder($overwrites = [])
    {
        return $this->signInAsAdmin()->postJson($this->endpoint,
            $this->orderAttributes($overwrites)
        );
    }

    private function orderAttributes(mixed $overwrites)
    {
        $attributes = Order::factory()->make()->toArray();
        $attributes = $attributes + [
                'isolated' => 0
            ];
        return array_merge($attributes, $overwrites);
    }
}
