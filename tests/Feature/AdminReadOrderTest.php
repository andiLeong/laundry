<?php

namespace Tests\Feature;

use App\Models\Order;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminReadOrderTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/order';

    /** @test */
    public function it_can_get_all_orders(): void
    {
        $orders = Order::factory(2)->create();
        $ids = $this->signInAsAdmin()->getJson($this->endpoint)
            ->assertOk()
            ->collect()
            ->pluck('id');

        $this->assertTrue($ids->contains($orders[0]->id));
        $this->assertTrue($ids->contains($orders[1]->id));
    }

    /** @test */
    public function order_is_order_by_latest_record(): void
    {
        $firstOrder = Order::factory()->create();
        $secondOrder = Order::factory()->create();
        $ids = $this->signInAsAdmin()->getJson($this->endpoint)
            ->collect();

        $this->assertEquals($ids[0]['id'],$secondOrder->id);
        $this->assertEquals($ids[1]['id'],$firstOrder->id);
    }

    /** @test */
    public function only_admin_can_access()
    {
        $this->signIn()->getJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function only_login_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
    }
}
