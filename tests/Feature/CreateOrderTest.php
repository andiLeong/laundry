<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class CreateOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_can_create_order(): void
    {
        $this->signInAsAdmin();
        $this->assertEmpty($this->user->orders);

        $this->withoutExceptionHandling();
        $response = $this->post('/api/orders', [
            'user_id' => $this->user->id,
            'amount' => 200,
        ]);

        $this->assertInstanceOf(Order::class, $this->user->fresh()->orders[0]);
        $response->assertSuccessful();
    }

    /** @test */
    public function customer_it_self_cant_create_order()
    {
        $customer = User::factory()->create();
        $this->actingAs($customer);

        $customer2 = User::factory()->create();
        $response = $this->postJson('/api/orders', [
            'user_id' => $customer2->id,
            'amount' => 200,
        ]);

        $response->assertForbidden();
    }

    /** @test */
    public function only_login_none_customer_can_create_order()
    {
        $this->postJson('/api/orders', [
            'user_id' => 100,
            'amount' => 200,
        ])->assertUnauthorized();
    }

    /** @test */
    public function amount_must_be_valid()
    {
        $name = 'amount';
        $rule = ['required','decimal'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function user_id_must_be_valid()
    {
        $name = 'user_id';
        $this->createOrder(['user_id' => 9988])->assertJsonValidationErrorFor($name);
    }

    public function createOrder($overwrites = [])
    {
        return $this->signInAsAdmin()->postJson('/api/orders',
            $this->orderAttributes($overwrites)
        );
    }

    private function orderAttributes(mixed $overwrites)
    {
        $attributes = Order::factory()->make()->toArray();
        return array_merge($attributes, $overwrites);
    }
}
