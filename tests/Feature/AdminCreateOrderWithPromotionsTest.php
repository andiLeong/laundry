<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class AdminCreateOrderWithPromotionsTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = 'api/admin/order';

    /** @test */
    public function isolated_must_be_valid()
    {
        $name = 'isolated';
        $this->signInAsAdmin()->postJson($this->endpoint, [$name => 2])->assertJsonValidationErrorFor($name);
        $this->signInAsAdmin()->postJson($this->endpoint, [$name => 3])->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function isolated_is_required_if_promotion_ids_is_present()
    {
        $promotion = Promotion::factory()->create();
        $this->signInAsAdmin()
            ->postJson($this->endpoint, ['promotion_ids' => [$promotion->id]])
            ->assertJsonValidationErrorFor('isolated');
    }

    /** @test */
    public function user_id_is_required_if_promotion_ids_is_present()
    {
        $promotion = Promotion::factory()->create();
        $this->signInAsAdmin()
            ->postJson($this->endpoint, ['promotion_ids' => [$promotion->id]])
            ->assertJsonValidationErrorFor('user_id');
    }

    /** @test */
    public function non_existed_promotion_ids_validation()
    {
        $name = 'promotion_ids';
        $rule = ['nullable', 'array'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );

        $response = $this->signInAsAdmin()
            ->postJson($this->endpoint, $this->orderAttributes(
                [$name => [100, 101]]
            ));
        $response->assertJsonValidationErrorFor($name);
        $this->assertTrue(in_array('promotions are invalid', $response->collect('errors')->get($name)));
    }

    /** @test */
    public function expired_promotion_ids_validation()
    {
        $name = 'promotion_ids';
        $expiredPromotion = Promotion::factory()->create([
            'until' => now()->subDays()
        ]);

        $payload = $this->orderAttributes([
            $name => [$expiredPromotion->id],
        ]);
        $response = $this->signInAsAdmin()->postJson($this->endpoint, $payload);
        $response->assertJsonValidationErrorFor($name);
        $this->assertTrue(in_array('promotions are invalid', $response->collect('errors')->get($name)));
    }

    /** @test */
    public function disabled_promotion_ids_validation()
    {
        $name = 'promotion_ids';
        $disabledPromotion = Promotion::factory()->create([
            'status' => false
        ]);

        $payload = $this->orderAttributes([
            $name => [$disabledPromotion->id],
        ]);
        $response = $this->signInAsAdmin()->postJson($this->endpoint, $payload);
        $response->assertJsonValidationErrorFor($name);
        $this->assertTrue(in_array('promotions are invalid', $response->collect('errors')->get($name)));
    }

    /** @test */
    public function promotion_class_validation()
    {
        $name = 'promotion_ids';
        $classNotExistedPromotion = Promotion::factory()->create([
            'class' => 'app\\Foo\\Bar\\SignUp.php',
        ]);

        $payload = $this->orderAttributes([
            $name => [$classNotExistedPromotion->id],
        ]);
        $response = $this->signInAsAdmin()->postJson($this->endpoint, $payload);
        $response->assertJsonValidationErrorFor($name);
        $this->assertTrue(in_array('promotion is not implemented', $response->collect('errors')->get($name)));
    }

    /** @test */
    public function if_isolated_flag_is_passed_then_the_promotion_must_be_isolated()
    {
        $name = 'promotion_ids';
        $nonIsolatedPromotion = Promotion::factory()->create([
            'isolated' => false
        ]);

        $payload = $this->orderAttributes([
            $name => [$nonIsolatedPromotion->id],
            'isolated' => 1
        ]);

        $response = $this->signInAsAdmin()->postJson($this->endpoint, $payload);
        $response->assertJsonValidationErrorFor($name);
        $this->assertTrue(in_array('promotions are invalid', $response->collect('errors')->get($name)));
    }

    /** @test */
    public function if_isolated_flag_is_passed_then_the_promotion_must_be_isolated_and_only_be_one()
    {
        $name = 'promotion_ids';
        $nonIsolatedPromotion = Promotion::factory(2)->create([
            'isolated' => true
        ]);

        $payload = $this->orderAttributes([
            $name => $nonIsolatedPromotion->pluck('id')->all(),
            'isolated' => 1
        ]);

        $response = $this->signInAsAdmin()->postJson($this->endpoint, $payload);
        $response->assertJsonValidationErrorFor($name);
        $this->assertTrue(in_array('isolated promotion is only allow one at a time', $response->collect('errors')->get($name)));
    }

    /** @test */
    public function user_id_is_properly_record_when_create_promotion()
    {
        $promotion = Promotion::factory()->create();
        $this->createOrderWithPromotions([$promotion->id], $user = User::factory()->create());

        $order = Order::first();
        $this->assertEquals($this->user->id,$order->creator_id);
        $this->assertEquals($user->id,$order->user_id);
    }

    /** @test */
    public function it_can_create_order_with_sign_up_discount_promotion()
    {
        $promotion = Promotion::factory()->create([
            'name' => 'sign up promotion'
        ]);
        $this->createOrderWithPromotions([$promotion->id]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(140, $order->amount);
    }

    /** @test */
    public function order_amount_is_correct_if_multiple_promotions_is_pass_and_valid()
    {
        $signUpPromotion = Promotion::factory()->create([
            'name' => 'sign up promotion'
        ]);
        $wednesdayPromotion = Promotion::factory()->create([
            'name' => 'Wednesday Promotion',
            'class' => 'App\Models\Promotions\WednesdayWasher',
        ]);
        $this->createOrderWithPromotions([$signUpPromotion->id, $wednesdayPromotion->id]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(100, $order->amount);
    }

    /** @test */
    public function after_order_is_created_with_promotion_it_should_record_order_promotion()
    {
        $signUpPromotion = Promotion::factory()->create([
            'name' => 'sign up promotion'
        ]);
        $wednesdayPromotion = Promotion::factory()->create([
            'name' => 'Wednesday Promotion',
            'class' => 'App\Models\Promotions\WednesdayWasher',
        ]);
        $this->createOrderWithPromotions([$signUpPromotion->id, $wednesdayPromotion->id]);

        $orderPromotion = OrderPromotion::all();
        $this->assertTrue($orderPromotion->pluck('promotion_id')->contains($signUpPromotion->id));
        $this->assertTrue($orderPromotion->pluck('promotion_id')->contains($wednesdayPromotion->id));
    }

    public function createOrderWithPromotions(array $promotions, User $user = null)
    {
        $user ??= User::factory()->create();
        $service = $this->getService();

        $this->assertDatabaseCount('order_promotions', 0);
        $this->createOrder([
            'promotion_ids' => $promotions,
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $this->assertDatabaseCount('order_promotions', count($promotions));
        $this->assertDatabaseCount('orders', 1);
    }

    public function createOrder($overwrites = [])
    {
        return $this->signInAsAdmin()->postJson($this->endpoint,
            $this->orderAttributes($overwrites)
        );
    }

    private function orderAttributes(mixed $overwrites)
    {
        $attributes = Order::factory()->make()->toArray();
        $attributes = $attributes + ['isolated' => 0];
        return array_merge($attributes, $overwrites);
    }

    public function getService($price = 200)
    {
        return Service::factory()->create([
            'price' => $price
        ]);
    }
}
