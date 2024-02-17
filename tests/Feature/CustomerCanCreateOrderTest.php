<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\OnlineOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\OrderImageCanBeValidated;
use Tests\TestAuthEndpoint;
use Tests\TestCase;
use Tests\TriggerCustomerCreateAction;
use Tests\Validate;

class CustomerCanCreateOrderTest extends TestCase
{
    use LazilyRefreshDatabase;
    use TestAuthEndpoint;
    use TriggerCustomerCreateAction;
    use OrderImageCanBeValidated;

    protected $endpoint = '/api/order';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Service::factory()->create();
        $this->user = User::factory()->create();
        $this->address = Address::factory()->create([
            'user_id' => $this->user->id
        ]);
        $this->imageArraySize = 2;
    }

    /** @test */
    public function address_id_is_required()
    {
        $name = 'address_id';
        $this->createOrder([$name => 9988])->assertJsonValidationErrorFor($name);
        $this->createOrder([$name => Address::factory()->create()->id])->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function pickup_is_required()
    {
        $name = 'pickup';
        $rule = ['required', 'date'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function description_is_nullable()
    {
        $name = 'description';
        $rule = ['nullable', 'string', 'max:255'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function delivery_is_nullable()
    {
        $name = 'delivery';
        $rule = ['nullable', 'date'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function image_is_nullable()
    {
        $name = 'image';
        $rule = ['nullable', 'array'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function product_id_is_nullable()
    {
        $name = 'product_ids';
        $rule = ['nullable', 'array'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function product_ids_validation()
    {
        $product1 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $response = $this->createOrder([
            'product_ids' => [
                ['id' => 888989],
                ['id' => 99],
            ],
        ]);

        $response2 = $this->createOrder([
            'product_ids' => [
                ['id' => $product1->id],
                ['id' => 99],
            ],
        ]);

        $this->assertValidateMessage('products are invalid', $response, 'product_ids');
        $this->assertValidateMessage('products are invalid', $response2, 'product_ids');
    }

    /** @test */
    public function product_must_to_have_enough_stocks()
    {
        $product = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $response = $this->createOrder([
            'product_ids' => [
                ['id' => $product->id, 'quantity' => 100],
            ],
        ]);

        $this->assertValidateMessage('stock is not enough', $response, 'product_ids');
    }

    /** @test */
    public function it_can_create_order()
    {
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('online_orders', 0);

        $this->createOrderWithMock()->assertSuccessful();

        $order = Order::latest()->first();
        $onlineOrder = OnlineOrder::where('order_id', $order->id)->first();

        $this->assertNotNull($order);
        $this->assertNotNull($onlineOrder);
    }

}
