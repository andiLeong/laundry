<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderImage;
use App\Models\Service;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;
use Mockery\MockInterface;
use Tests\AdminAuthorization;
use Tests\OrderImageCanBeValidated;
use Tests\TestCase;
use Tests\Validate;

class AdminUpdateOrderTest extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderImageCanBeValidated;
    use AdminAuthorization;

    protected $endpoint;
    protected $route = '/api/admin/order/';

    protected function setUp(): void
    {
        parent::setUp();
        $this->order = Order::factory()->create();
        $this->setEndpoint($this->order->id);
        $this->imageCreation = 'updateOrder';
        $this->method = 'patchJson';
    }

    /** @test */
    public function order_id_must_be_correct()
    {
        $this->setEndpoint(98998598)->updateorder()->assertNotFound();
    }

    /** @test */
    public function amount_is_required()
    {
        $name = 'amount';
        $rule = ['decimal:0,4'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->updateOrder($payload)
        );
    }

    /** @test */
    public function service_id_is_required()
    {
        $name = 'service_id';
        $rule = ['required'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->updateOrder($payload)
        );
    }

    /** @test */
    public function service_id_must_be_valid()
    {
        $this->updateorder(['service_id' => 9999999987])
            ->assertJsonValidationErrorFor('service_id');
    }

    /** @test */
    public function description_is_nullable()
    {
        $name = 'description';
        $rule = ['nullable','string','max:255'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->updateOrder($payload)
        );
    }

    /** @test */
    public function it_can_update_description(): void
    {
        $description = Str::random(5);
        $this->updateorder(['description' => $description])
            ->assertstatus(200);
        $this->assertequals($description, $this->order->fresh()->description);
    }

    /** @test */
    public function it_can_update_service(): void
    {
        $service = Service::factory()->create();
        $this->updateorder(['service_id' => $service->id])
            ->assertstatus(200);
        $this->assertequals($service->id, $this->order->fresh()->service_id);
    }

    /** @test */
    public function it_can_update_amount(): void
    {
        $this->order = Order::factory()->create([
            'amount' => 100,
            'product_amount' => 10,
            'total_amount' => 110,
        ]);

        $this->setEndpoint($this->order->id)
            ->updateorder(['amount' => 150])
            ->assertstatus(200);
        $this->assertequals(150, $this->order->fresh()->amount);
        $this->assertequals(160, $this->order->fresh()->total_amount);
    }

    /** @test */
    public function it_can_add_order_image(): void
    {
        $this->mock(Filesystem::class, function (MockInterface $mock) {
            $mock->shouldReceive('putFileAs')->andReturn('1_' . Str::random() . '.jpg');
        });
        $this->assertDatabaseCount('order_images',0);

        $this->updateorder(['image' => [$this->generateImageFile()]])
            ->assertstatus(200);
        $orderImage = OrderImage::where('order_id', $this->order->id)->first();

        $this->assertNotNull($orderImage);
        $this->assertDatabaseCount('order_images',1);
    }

   protected function updateOrder($overwrites = [])
    {
        $payload = array_merge($this->order->toArray(), $overwrites);
        return $this->signInAsAdmin()->patchJson($this->endpoint, $payload);
    }

    private function setEndpoint(mixed $id)
    {
        $this->endpoint = $this->route . $id;
        return $this;
    }
}
