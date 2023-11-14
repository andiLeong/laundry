<?php

namespace Tests\Feature;

use App\Models\Order;
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
        $this->update($order->id,'paid');
        $this->update($order2->id,'paid');

        $this->assertFalse($order->fresh()->paid);
        $this->assertTrue($order2->fresh()->paid);
    }

    /** @test */
    public function it_can_toggle_order_issue_invoice_status()
    {
        $order = Order::factory()->create();
        $order2 = Order::factory()->create(['issued_invoice' => true]);
        $this->update($order->id,'issued_invoice');
        $this->update($order2->id,'issued_invoice');

        $this->assertTrue($order->fresh()->issued_invoice);
        $this->assertFalse($order2->fresh()->issued_invoice);
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