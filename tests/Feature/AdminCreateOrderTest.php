<?php


use App\Models\Enum\OrderPayment;
use App\Models\Enum\OrderType;
use App\Models\Order;
use App\Models\OrderImage;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\OrderCanBeCreated;
use Tests\OrderImageCanBeValidated;
use Tests\TestCase;
use Tests\UserCanBeVerified;
use Tests\Validate;

class AdminCreateOrderTest extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderCanBeCreated;
    use UserCanBeVerified;
    use OrderImageCanBeValidated;

    protected string $phone = '09060785699';
    private mixed $id;

    /** @test */
    public function it_can_create_order(): void
    {
        $this->assertDatabaseCount('orders', 0);
        $response = $this->createOrderWithMock();
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
        $this->createOrderWithMock([$name => null])->assertJsonMissingValidationErrors($name);
    }

    /** @test */
    public function payment_must_be_valid()
    {
        $name = 'payment';
        $rule = ['required', 'in:1,2'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function type_must_be_valid()
    {
        $name = 'type';
        $rule = ['required', 'in:'. OrderType::ONLINE->value . ',' . OrderType::WALKIN->value];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function paid_must_be_valid()
    {
        $name = 'paid';
        $rule = ['required', 'in:0,1'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }

    /** @test */
    public function description_must_be_valid()
    {
        $name = 'description';
        $rule = ['nullable', 'string'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
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
    public function image_is_nullable()
    {
        $name = 'image';
        $rule = ['nullable', 'array'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );
    }


    /** @test */
    public function it_use_service_price_as_amount_if_service_id_is_provided()
    {
        $order = Order::first();
        $this->assertNull($order);
        $service = Service::factory()->create([
            'price' => 200
        ]);

        $this->createOrderWithMock([
            'service_id' => $service->id,
            'amount' => 100,
        ]);

        $order = Order::first();
        $this->assertEquals(100, $order->amount);
    }

    /** @test */
    public function its_amount_default_to_service_price()
    {
        $description = Str::random();
        $service = Service::factory()->create(['price' => 201]);
        $this->createOrderWithMock([
            'service_id' => $service->id,
            'amount' => null,
            'description' => $description,
        ]);

        $order = Order::where('description', $description)->first();
        $this->assertEquals(201, $order->amount);
    }

    /** @test */
    public function when_create_order_customer_can_create_order_with_product()
    {
        $this->withoutExceptionHandling();
        $this->assertDatabaseCount('order_products', 0);

        $service = Service::factory()->create(['price' => 201]);
        $product1 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $this->createOrderWithMock([
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
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => 1,
            'name' => $product1->name,
            'price' => $product1->price,
            'total_price' => $product1->price * 1,
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'name' => $product2->name,
            'price' => $product1->price,
            'total_price' => $product1->price * 1,
        ]);

        $this->assertEquals($product1->stock - 1, $product1->fresh()->stock);
        $this->assertEquals($product2->stock - 1, $product2->fresh()->stock);
    }

    /** @test */
    public function when_create_order_customer_can_choose_multiple_products()
    {
        $this->withoutExceptionHandling();
        $quantity = 5;
        $quantity2 = 3;
        $service = Service::factory()->create(['price' => 201]);
        $product1 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 70, 'stock' => 10]);
        $this->createOrderWithMock([
            'service_id' => $service->id,
            'product_ids' => [
                ['id' => $product1->id, 'quantity' => $quantity],
                ['id' => $product2->id, 'quantity' => $quantity2],
            ],
            'amount' => null,
        ]);

        $order = Order::with('products')->first();
        $productAmount = $product1->price * $quantity + $product2->price * $quantity2;
        $this->assertEquals($quantity, $order->products[0]->quantity);
        $this->assertEquals($quantity2, $order->products[1]->quantity);
        $this->assertEquals($productAmount + $service->price, $order->total_amount);
        $this->assertEquals($productAmount, $order->product_amount);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => $quantity,
            'name' => $product1->name,
            'price' => $product1->price,
            'total_price' => $product1->price * $quantity
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

        $this->assertValidateMessage('products are invalid', $response, 'product_ids');
        $this->assertValidateMessage('products are invalid', $response2, 'product_ids');
    }

    /** @test */
    public function product_must_to_have_enough_stocks()
    {
        $product = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $response = $this->createOrder([
            'service_id' => $this->getService()->id,
            'product_ids' => [
                ['id' => $product->id, 'quantity' => 100],
            ],
        ]);

        $this->assertValidateMessage('stock is not enough', $response, 'product_ids');
    }

    /** @test */
    public function order_paid_record_is_also_created_if_when_order_create_order_is_paid(): void
    {
        $this->assertDatabaseCount('order_paid', 0);
        $response = $this->createOrderWithMock(['paid' => 1]);
        $this->assertDatabaseCount('order_paid', 1);
        $response->assertSuccessful();
    }

    /** @test */
    public function order_paid_record_is_not_created_if_when_order_create_order_is_not_paid(): void
    {
        $this->assertDatabaseCount('order_paid', 0);
        $response = $this->createOrderWithMock(['paid' => 0]);
        $this->assertDatabaseCount('order_paid', 0);
        $response->assertSuccessful();
    }

    /** @test */
    public function it_can_create_order_image()
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->shouldReceive('putFileAs')->andReturn('1_' . Str::random() . '.jpg');
        });
        $this->assertDatabaseEmpty('orders');
        $this->assertDatabaseEmpty('order_images');
        $this->createOrderWithMock([
            'image' => [UploadedFile::fake()->create('avatar.jpg', 501)]
        ]);
        $order = Order::first();
        $image = OrderImage::where('order_id', $order->id)->first();
        $file = explode('/', $image->path);
        $name = end($file);
        $this->assertTrue(str_starts_with($name, '1_'));
        $this->assertTrue(str_ends_with($name, '.jpg'));
        $this->assertNotNull($image);
    }

    private function orderAttributes(mixed $overwrites)
    {
        $attributes = Order::factory()->make()->toArray();
        $attributes['payment'] = OrderPayment::CASH->value;
        $attributes['type'] = OrderType::WALKIN->value;
        return array_merge($attributes, $overwrites);
    }
}
