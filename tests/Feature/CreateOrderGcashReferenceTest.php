<?php

namespace Tests\Feature;

use App\Models\Enum\OrderPayment;
use App\Models\GcashOrder;
use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class CreateOrderGcashReferenceTest extends TestCase
{
    use LazilyRefreshDatabase;

    public $endpoint = '/api/admin/gcash-order';

    /** @test */
    public function only_gcash_order_can_be_created(): void
    {
        $order = Order::factory()->create();
        $this->signInAsAdmin()->postJson($this->endpoint, [
            'order_id' => $order->id
        ])->assertJsonValidationErrorFor('order_id');
    }

    /** @test */
    public function order_id_is_required()
    {
        $this->signInAsAdmin()->postJson($this->endpoint)->assertJsonValidationErrorFor('order_id');
    }

    /** @test */
    public function reference_number_is_required()
    {
        $this->signInAsAdmin()->postJson($this->endpoint)->assertJsonValidationErrorFor('reference_number');
    }

    /** @test */
    public function order_id_must_be_unique()
    {
        $this->signInAsAdmin()->postJson($this->endpoint,[
            'order_id' => GcashOrder::factory()->create()->order_id,
            'reference_number' => '1111',
        ])->assertJsonValidationErrorFor('order_id');
    }

    /** @test */
    public function reference_number_must_be_unique()
    {
        GcashOrder::factory()->create(['reference_number' => 995]);
        $order = Order::factory()->create(['payment' => OrderPayment::GCASH->value]);

        $this->signInAsAdmin()->postJson($this->endpoint,[
            'order_id' => $order->id,
            'reference_number' => '995',
        ])->assertJsonValidationErrorFor('reference_number');
    }

    /** @test */
    public function only_staff_and_admin_can_access()
    {
        $this->postJson($this->endpoint)->assertUnauthorized();

        $customer = $this->customer();
        $this->signIn($customer)->postJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function it_can_create_gcash_order_reference_number()
    {
        $this->assertDatabaseEmpty('gcash_orders');

        $order = Order::factory()->create(['payment' => OrderPayment::GCASH->value]);
        $this->signInAsAdmin()->postJson($this->endpoint, [
            'order_id' => $order->id,
            'reference_number' => 'sdxccxvvxv'
        ])->assertSuccessful();

        $this->assertDatabaseHas('gcash_orders', [
            'order_id' => $order->id,
            'reference_number' => 'sdxccxvvxv'
        ]);
    }
}
