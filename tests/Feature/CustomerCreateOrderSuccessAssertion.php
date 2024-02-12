<?php

namespace Tests\Feature;

use App\Models\Address;
use App\Models\Enum\OnlineOrderStatus;
use App\Models\Enum\OrderPayment;
use App\Models\Enum\OrderType;
use App\Models\OnlineOrder;
use App\Models\Order;
use App\Models\Product;
use App\Models\Service;
use App\Models\User;
use App\Notification\Telegram;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\TriggerCustomerCreateAction;

class CustomerCreateOrderSuccessAssertion extends TestCase
{
    use LazilyRefreshDatabase;
    use TriggerCustomerCreateAction;

    protected $endpoint = '/api/order';

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = Service::factory()->create();
        $this->user = User::factory()->create();
        $this->address = Address::factory()->create([
            'user_id' => $this->user->id
        ]);
    }

    /** @test */
    public function after_order_created_amount_is_correct()
    {
        $this->createOrderWithMock();
        $order = Order::latest()->first();

        $this->assertEquals($order->total_amount, $order->service->price);
        $this->assertEquals($order->amount, $order->service->price);
        $this->assertEquals(0, $order->product_amount);
    }

    /** @test */
    public function after_order_created_product_amount_is_correct()
    {
        $quantity = 5;
        $quantity2 = 3;
        $product1 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 70, 'stock' => 10]);
        $this->createOrderWithMock([
            'product_ids' => [
                ['id' => $product1->id, 'quantity' => $quantity],
                ['id' => $product2->id, 'quantity' => $quantity2],
            ],
        ]);
        $productAmount = $product1->price * $quantity + $product2->price * $quantity2;
        $order = Order::latest()->first();

        $this->assertEquals($order->amount, $order->service->price);
        $this->assertEquals($productAmount + $order->amount, $order->total_amount);
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
    public function after_order_created_order_paid_is_no_records()
    {
        $this->assertDatabaseEmpty('order_paid');
        $this->createOrderWithMock();
        Order::latest()->first();
        $this->assertDatabaseEmpty('order_paid');
    }

    /** @test */
    public function after_order_created_order_is_main_order()
    {
        $this->createOrderWithMock();
        $order = Order::latest()->first();
        $this->assertEquals(0, $order->parent_id);
    }

    /** @test */
    public function after_order_created_most_requested_service_is_the_order_service()
    {
        $this->createOrderWithMock();

        $order = Order::latest()->first();
        $mostRequestedService = Service::mostRequested();

        $this->assertEquals($order->service_id, $mostRequestedService->id);
        $this->assertEquals($order->service_id, $this->service->id);
    }

    /** @test */
    public function after_order_created_user_id_is_recorded_properly()
    {
        $this->createOrderWithMock();
        $order = Order::latest()->first();
        $this->assertEquals($order->creator_id, $this->user->id);
        $this->assertEquals($order->user_id, $this->user->id);
    }

    /** @test */
    public function after_order_created_order_type_should_be_online()
    {
        $this->createOrderWithMock();
        $order = Order::latest()->first();
        $this->assertEquals($order->type, OrderType::ONLINE->toLower());
    }

    /** @test */
    public function after_order_created_it_is_not_paid_and_payment_default_to_cash()
    {
        $this->createOrderWithMock();
        $order = Order::latest()->first();
        $this->assertEquals($order->payment, OrderPayment::CASH->toLower());
        $this->assertFalse($order->paid);
    }

    /** @test */
    public function after_order_created_online_order_status_is_pending()
    {
        $this->createOrderWithMock();
        $order = Order::latest()->first();
        $onlineOrder = OnlineOrder::where('order_id', $order->id)->first();
        $this->assertEquals($onlineOrder->status, OnlineOrderStatus::PENDING_PICKUP->toLower());
    }

    /** @test */
    public function after_order_created_online_order_address_is_correct()
    {
        $this->createOrderWithMock();
        $order = Order::latest()->first();
        $onlineOrder = OnlineOrder::where('order_id', $order->id)->first();
        $this->assertEquals($onlineOrder->address_id, $this->address->id);
    }

    /** @test */
    public function after_order_created_online_order_delivery_fee_is_zero()
    {
        //todo implements delivery fee charges
        $this->createOrderWithMock();
        $order = Order::latest()->first();
        $onlineOrder = OnlineOrder::where('order_id', $order->id)->first();
        $this->assertEquals(0, $onlineOrder->delivery_fee);
    }

    /** @test */
    public function after_order_created_order_description_recorded()
    {
        $this->createOrderWithMock([
            'description' => 'make sure dry',
        ]);
        $order = Order::latest()->first();
        $this->assertEquals('make sure dry', $order->description);
    }

    /** @test */
    public function after_order_created_online_order_recorded()
    {
        $pickup = now()->addHours(3);
        $this->createOrderWithMock([
            'pickup' => $pickup->toDateTimeString(),
            'delivery' => $delivery = $pickup->copy()->addHours(3)->toDateTimeString(),
        ]);

        $order = Order::latest()->first();
        $onlineOrder = OnlineOrder::where('order_id', $order->id)->first();

        $this->assertNotNull($onlineOrder);
        $this->assertEquals($pickup, $onlineOrder->pickup);
        $this->assertNull($onlineOrder->pickup_at);
        $this->assertNull($onlineOrder->deliver_at);
        $this->assertEquals($delivery, $onlineOrder->delivery);
    }

    /** @test */
    public function after_order_created_delivery_time_is_12_hours_forwarded_if_delivery_is_not_provided()
    {
        $this->mock(Telegram::class, function (MockInterface $mock) {
            $mock->shouldReceive('sendOrderCreatedNotification')->once()->andReturn(true);
        });

        $pickup = now()->addHours(3);
        $attributes = OnlineOrder::factory()->make()->toArray();
        $attributes['address_id'] = $this->address->id;
        $attributes['pickup'] = $pickup->toDateTimeString();
        $attributes['delivery'] = null;
        $this->signIn($this->user)->postJson($this->endpoint, $attributes);

        $order = Order::latest()->first();
        $onlineOrder = OnlineOrder::where('order_id', $order->id)->first();

        $this->assertEquals($pickup->copy()->addHours(12)->toDateTimeString(), $onlineOrder->delivery);
    }

    /** @test */
    public function after_order_created_order_image_is_recorded()
    {
        $this->assertDatabaseCount('order_images', 0);
        $fake = UploadedFile::fake()->image('order.jpg');
        $this->mockFileSystem()->createOrderWithMock(['image' => [$fake]]);
        $order = Order::latest()->first();
        $image = $order->images->first();

        $this->assertNotEmpty($image->path);
        $this->assertEquals($image->creator->id, $this->user->id);
        $this->assertNotNull($image);
        $this->assertDatabaseCount('order_images', 1);
    }

    /** @test */
    public function after_order_created_order_image_name_is_correctly_set()
    {
        $this->assertDatabaseCount('order_images', 0);
        $fake = UploadedFile::fake()->image('order.jpg');
        $this->mockFileSystem(2)->createOrderWithMock(['image' => [$fake], 'description' => 'hay']);
        $order = Order::latest()->first();
        $image = $order->images->first();

        $file = explode('/', $image->path);
        $name = end($file);
        $this->assertTrue(str_starts_with($name, $order->id . '_'));
        $this->assertTrue(str_ends_with($name, '.' . $fake->extension()));
    }

    public function mockFileSystem($id = 1)
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) use($id) {
            $mock->shouldReceive('putFileAs')->andReturn($id . '_' . Str::random() . '.jpg');
        });
        return $this;
    }
}
