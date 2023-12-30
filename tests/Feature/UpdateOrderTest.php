<?php

namespace Tests\Feature;

use App\Models\Enum\OrderPayment;
use App\Models\Order;
use App\Models\OrderPaid;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UpdateOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    public $endpoint = '/api/admin/order';

    /** @test */
    public function it_mast_be_sign_in_as_admin_or_staff_to_perform(): void
    {
        $order = Order::factory()->create();
        $response = $this->signIn($this->customer())->patchJson($this->getEndpoint($order->id, 'paid'));
        $response->assertStatus(403);
    }

    /** @test */
    public function it_gets_validation_error_if_column_passed_is_not_valid()
    {
        $order = Order::factory()->create();
        $response = $this->signInAsAdmin()->patchJson($this->getEndpoint($order->id, 'foo'));
        $response->assertStatus(422);
        $this->assertEquals('We can\'t handle your request, please try again later', $response->json('message'));
    }

    /** @test */
    public function it_gets_404_if_order_id_passed_is_not_exists()
    {
        $response = $this->signInAsAdmin()->patchJson($this->getEndpoint(99999, 'foo'))->assertNotFound();
        $this->assertEquals('Order is not existed', $response->json('message'));
    }

    /** @test */
    public function it_can_toggle_order_paid_status()
    {
        $order = Order::factory()->create();
        $order2 = Order::factory()->create(['paid' => false]);
        $this->update($order->id, 'paid');
        $this->update($order2->id, 'paid');

        $this->assertFalse($order->fresh()->paid);
        $this->assertTrue($order2->fresh()->paid);
    }

    /** @test */
    public function it_can_toggle_order_issue_invoice_status()
    {
        $order = Order::factory()->create();
        $order2 = Order::factory()->create(['issued_invoice' => true]);
        $this->update($order->id, 'issued_invoice');
        $this->update($order2->id, 'issued_invoice');

        $this->assertTrue($order->fresh()->issued_invoice);
        $this->assertFalse($order2->fresh()->issued_invoice);
    }

    /** @test */
    public function it_can_toggle_order_payment_status()
    {
        $order = Order::factory()->create();
        $order2 = Order::factory()->create(['payment' => OrderPayment::GCASH->value]);
        $this->update($order->id, 'payment');
        $this->update($order2->id, 'payment');

        $this->assertEquals(OrderPayment::GCASH->toLower(), $order->fresh()->payment);
        $this->assertEquals(OrderPayment::CASH->toLower(), $order2->fresh()->payment);
    }

    /** @test */
    public function when_mark_order_is_paid_order_paid_is_recorded()
    {
        Carbon::setTestNow(now()->subDays(2));
        $this->assertDatabaseCount('order_paid', 0);

        $order = Order::factory()->create(['paid' => false]);
        $this->update($order->id, 'paid');

        $this->assertDatabaseHas('order_paid', [
            'order_id' => $order->id,
            'amount' => $order->total_amount,
            'creator_id' => $this->user->id,
            'created_at' => now()->toDateTimeString()
        ]);
    }

    /** @test */
    public function when_mark_order_is_unpaid_order_paid_record_should_be_deleted()
    {
        $order = Order::factory()->create(['paid' => true]);
        OrderPaid::factory()->create(['order_id' => $order->id]);

        $this->assertDatabaseCount('order_paid', 1);
        $this->update($order->id, 'paid');
        $this->assertDatabaseCount('order_paid', 0);
    }

    public function update($id, $column)
    {
        return $this->signInAsAdmin()->patchJson($this->getEndpoint($id, $column));
    }

    public function getEndpoint($id, $column)
    {
        return $this->endpoint . '/' . $id . '/' . $column;
    }
}
