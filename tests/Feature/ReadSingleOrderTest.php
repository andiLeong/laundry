<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Product;
use App\Models\ProductOrder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadSingleOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = '/api/order/';

    /** @test */
    public function it_can_get_order_detail()
    {
        $john = $this->customer();
        $order = Order::factory()->create(['user_id' => $john]);
        $product = Product::factory(2)
            ->create()
            ->map(function ($product) {
                return ['id' => $product->id];
            })
            ->each(fn($product) => ProductOrder::associate($order, $product)
            );

        $response = $this->signIn($john)
            ->getJson($this->endpoint($order->id))
            ->assertSuccessful()
            ->json();

        $this->assertEquals($order->service->name, $response['service_name']);
        $this->assertEquals($order->amount, $response['amount']);
        $this->assertEquals($order->total_amount, $response['total_amount']);
        $this->assertEquals($order->product_amount, $response['product_amount']);
        $this->assertEquals($order->paid, $response['paid']);
        $this->assertEquals($order->payment, $response['payment']);
        $this->assertEquals($order->created_at->toJson(), $response['created_at']);

        $this->assertColumnsSame(['id', 'service_name', 'product_order', 'amount', 'total_amount', 'product_amount', 'paid', 'payment', 'created_at', 'user_id'], array_keys($response));

        foreach ($response['product_order'] as $productOrder) {
            $this->assertColumnsSame(['name', 'price', 'quantity'], array_keys($productOrder));
            $this->assertEquals(1, $productOrder['quantity']);
        }
    }

    /** @test */
    public function it_cant_read_other_user_order_that_not_belongs_to_you(): void
    {
        $john = $this->customer();
        $gary = $this->customer();

        $order = Order::factory()->create(['user_id' => $john]);

        $message = $this->signIn($gary)
            ->getJson($this->endpoint($order->id))
            ->assertStatus(404)
            ->json('message');

        $this->assertEquals('Order not found', $message);
    }

    /** @test */
    public function it_gets_404_if_order_is_not_exist(): void
    {
        $gary = $this->customer();

        $message = $this->signIn($gary)
            ->getJson($this->endpoint(99999999999999999999))
            ->assertStatus(404)
            ->json('message');

        $this->assertEquals('Order not found', $message);
    }

    /** @test */
    public function unauthenticated_user_gets_401(): void
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
    }

    private function endpoint($id)
    {
        return $this->endpoint . $id;
    }
}
