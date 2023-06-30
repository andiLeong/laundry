<?php

namespace Tests\Unit;

use App\Models\Order;
use App\Models\User;
use App\Models\VerificationToken;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UserTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function an_user_should_contains_necessary_attributes(): void
    {
        $user = User::factory([
            'phone' => '09000000000',
            'first_name' => 'nancy',
            'middle_name' => 'julie',
            'last_name' => 'macy',
        ])->create();

        $this->assertEquals('09000000000', $user->phone);
        $this->assertEquals('nancy', $user->first_name);
        $this->assertEquals('julie', $user->middle_name);
        $this->assertEquals('macy', $user->last_name);
        $this->assertEquals('customer', $user->type);
    }

    /** @test */
    public function it_may_not_has_middle_name(): void
    {
        $user = User::factory(['middle_name' => null])->create();
        $this->assertNull($user->middle_name);
    }

    /** @test */
    public function it_can_has_orders(): void
    {
        $user = User::factory()->create();
        $orders = Order::factory(2)->create(['user_id' => $user->id]);

        $this->assertTrue(in_array($orders[0]->id, $user->orders->pluck('id')->all()));
        $this->assertTrue(in_array($orders[1]->id, $user->orders->pluck('id')->all()));
    }

    /** @test */
    public function it_can_fetch_the_latest_verification_token()
    {
        $user = User::factory()->create();
        $firstToken = VerificationToken::factory()->create(['user_id' => $user]);
        $secondToken = VerificationToken::factory()->create(['user_id' => $user]);

        $this->assertEquals($secondToken->token, $user->verification->token);
        $this->assertEquals($secondToken->user_id, $user->id);
    }
}
