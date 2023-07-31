<?php


use App\Models\Order;
use App\Models\Product;
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
    public function only_login_admin_or_staff_can_create_order()
    {
        $this->postJson($this->endpoint, [
            'user_id' => 100,
            'amount' => 200,
        ])->assertUnauthorized();

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

    /** @test */
    public function when_create_order_customer_can_create_order_with_product()
    {
        $this->withoutExceptionHandling();
        $this->assertDatabaseCount('product_orders', 0);

        $service = Service::factory()->create(['price' => 201]);
        $product1 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $this->createOrder([
            'service_id' => $service->id,
            'product_ids' => [
                ['id' => $product1->id],
                ['id' => $product2->id],
            ],
            'amount' => null,
        ]);

        $order = Order::first();
        $this->assertEquals($service->price, $order->amount);
        $this->assertEquals($product1->price + $product2->price + $service->price, $order->total_amount);
        $this->assertEquals($product1->price + $product2->price, $order->product_amount);
        $this->assertDatabaseHas('product_orders', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 1,
        ]);

        $this->assertDatabaseHas('product_orders', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
        ]);

        $this->assertEquals($product1->stock - 1, $product1->fresh()->stock);
        $this->assertEquals($product2->stock - 1, $product2->fresh()->stock);
    }

    /** @test */
    public function when_create_order_customer_can_choose_multiple_products()
    {
        $this->withoutExceptionHandling();
        $quantity = 5;
        $service = Service::factory()->create(['price' => 201]);
        $product1 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 70, 'stock' => 10]);
        $this->createOrder([
            'service_id' => $service->id,
            'product_ids' => [
                ['id' => $product1->id, 'quantity' => $quantity],
                ['id' => $product2->id, 'quantity' => $quantity],
            ],
            'amount' => null,
        ]);

        $order = Order::with('productOrder')->first();
        $productAmount = $product1->price * $quantity + $product2->price * $quantity;
        $this->assertEquals(5, $order->productOrder[0]->pivot->quantity);
        $this->assertEquals($productAmount + $service->price, $order->total_amount);
        $this->assertEquals($productAmount, $order->product_amount);

        $this->assertDatabaseHas('product_orders', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => $quantity
        ]);
        $this->assertEquals($product1->stock - 5, $product1->fresh()->stock);
    }

    /** @test */
    public function product_id_validation()
    {
        $product1 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $service = $this->getService();
        $response = $this->createOrder([
            'service_id' => $service->id,
            'product_ids' => [
                ['id' => 888989],
                ['id' => 99],
            ],
        ]);

        $response2 = $this->createOrder([
            'service_id' => $service->id,
            'product_ids' => [
                ['id' => $product1->id],
                ['id' => 99],
            ],
        ]);

        $this->assertValidateMessage('products are invalid', $response,'product_ids');
        $this->assertValidateMessage('products are invalid', $response2,'product_ids');
    }

    /** @test */
    public function product_must_to_have_enough_stocks()
    {
        $product = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $response = $this->createOrder([
            'service_id' => $this->getService()->id,
            'product_ids' => [
                ['id' => $product->id,'quantity' => 100],
            ],
        ]);

        $this->assertValidateMessage('stock is not enough', $response,'product_ids');
    }

    private function orderAttributes(mixed $overwrites)
    {
        $attributes = Order::factory()->make()->toArray();
        return array_merge($attributes, $overwrites);
    }
}
