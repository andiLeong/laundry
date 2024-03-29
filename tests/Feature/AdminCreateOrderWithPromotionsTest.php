<?php

namespace Tests\Feature;

use App\Models\Enum\OrderPayment;
use App\Models\Enum\OrderType;
use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\OrderCanBeCreated;
use Tests\TestCase;
use Tests\Validate;

class AdminCreateOrderWithPromotionsTest extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderCanBeCreated;

    /** @test */
    public function user_id_is_required_if_promotion_ids_is_present()
    {
        $promotion = Promotion::factory()->create();
        $this->signInAsAdmin()
            ->postJson($this->endpoint, ['promotion_ids' => [$promotion->id]])
            ->assertJsonValidationErrorFor('user_id');
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
    public function non_existed_promotion_ids_validation()
    {
        $name = 'promotion_ids';
        $rule = ['required', 'array'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createOrder($payload)
        );

        $response = $this->signInAsAdmin()
            ->postJson($this->endpoint, $this->orderAttributes(
                [$name => [100, 101]]
            ));
        $this->assertValidateMessage('promotions are invalid', $response, $name);
    }

    /** @test */
    public function not_started_promotion_ids_validation()
    {
        $name = 'promotion_ids';
        $notStartedPromotion = Promotion::factory()->create([
            'start' => now()->addDays(2),
            'until' => null,
        ]);

        $payload = $this->orderAttributes([
            $name => [$notStartedPromotion->id],
        ]);
        $response = $this->signInAsAdmin()->postJson($this->endpoint, $payload);
        $this->assertValidateMessage('promotions are invalid', $response, $name);
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
        $this->assertValidateMessage('promotions are invalid', $response, $name);
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
        $this->assertValidateMessage('promotions are invalid', $response, $name);
    }

    /** @test */
    public function promotion_id_validation()
    {
        $name = 'promotion_ids';
        $response = $this->createOrder([$name => []]);
        $this->assertValidateMessage('The promotion ids field is required.', $response, $name);
        $response = $this->createOrder([$name => null]);
        $this->assertValidateMessage('The promotion ids field is required.', $response, $name);
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
        $this->assertValidateMessage('promotion is not implemented', $response, $name);
    }

    /** @test */
    public function service_id_must_valid()
    {
        $name = 'service_id';
        $this->createOrder([$name => 9988])->assertJsonValidationErrorFor($name);
    }

    /** @test */
    public function if_isolated_flag_is_passed_then_the_promotion_must_be_isolated_and_only_be_one()
    {
        $name = 'promotion_ids';
        $isolatedPromotion = Promotion::factory()->create([
            'isolated' => true
        ]);
        $nonIsolatedPromotion = Promotion::factory()->create([
            'isolated' => false
        ]);

        $payload = $this->orderAttributes([
            $name => [$isolatedPromotion->id, $nonIsolatedPromotion->id]
        ]);

        $response = $this->signInAsAdmin()->postJson($this->endpoint, $payload);
        $this->assertValidateMessage('isolated promotion is only allow one at a time', $response, $name);
    }

    /** @test */
    public function user_id_is_properly_record_when_create_promotion()
    {
        $promotion = Promotion::factory()->create();
        $this->createOrderWithPromotionsAndMock([$promotion->id], $user = User::factory()->create());

        $order = Order::first();
        $this->assertEquals($this->user->id, $order->creator_id);
        $this->assertEquals($user->id, $order->user_id);
    }

    /** @test */
    public function order_amount_is_correct_if_multiple_promotions_is_pass_and_valid()
    {
        $signUpPromotion = $this->getPromotion();
        $wednesdayPromotion = $this->getWednesdayPromotion();
        $this->createOrderWithPromotionsAndMock([$signUpPromotion->id, $wednesdayPromotion->id]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals(80, $order->amount);
    }

    /** @test */
    public function after_order_is_created_with_promotion_it_should_record_order_promotion()
    {
        $signUpPromotion = $this->getPromotion();
        $wednesdayPromotion = $this->getWednesdayPromotion();
        $this->createOrderWithPromotionsAndMock([$signUpPromotion->id, $wednesdayPromotion->id]);

        $orderPromotion = OrderPromotion::all();
        $this->assertTrue($orderPromotion->pluck('promotion_id')->contains($signUpPromotion->id));
        $this->assertTrue($orderPromotion->pluck('promotion_id')->contains($wednesdayPromotion->id));
    }

    /** @test */
    public function some_promotions_that_will_not_apply_discount_like_reward_gift_certificates()
    {
        $rewardGiftCertificatePromotion = $this->rewardGiftCertificatePromotion();
        $this->createOrderWithPromotionsAndMock([$rewardGiftCertificatePromotion->id]);

        $orderId = OrderPromotion::where('promotion_id',
            $rewardGiftCertificatePromotion->id)->first('order_id')->order_id;
        $order = Order::find($orderId);
        $this->assertEquals(200, $order->amount);
    }

    /** @test */
    public function order_amount_is_correct_if_one_promotion_is_non_discount_apply_promotion_another_is_apply_discount()
    {
        $signUpPromotion = $this->getPromotion();
        $rewardGiftCertificatePromotion = $this->rewardGiftCertificatePromotion();
        $this->createOrderWithPromotionsAndMock([$signUpPromotion->id, $rewardGiftCertificatePromotion->id]);

        $orderId = OrderPromotion::where('promotion_id',
            $rewardGiftCertificatePromotion->id)->first('order_id')->order_id;
        $order = Order::find($orderId);
        $this->assertEquals(100, $order->amount);
    }

    /** @test */
    public function if_all_promotions_id_are_not_qualified_validation_error_will_throw()
    {
        $unqualified = $this->unqualifiedPromotion();
        $unqualified2 = $this->unqualifiedPromotion2();
        $service = $this->getService();
        $user = User::factory()->create();

        $response = $this->createOrder([
            'promotion_ids' => [$unqualified->id, $unqualified2->id],
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $this->assertValidateMessage('Sorry You are not qualified with these promotions', $response, 'promotion_ids');
    }

    /** @test */
    public function only_qualified_promotion_discount_will_be_applied()
    {
        $unqualified = $this->unqualifiedPromotion();
        $unqualified2 = $this->unqualifiedPromotion2();
        $signUpPromotion = $this->getPromotion();
        $service = $this->getService();
        $user = User::factory()->create();

        $this->createOrderWithMock([
            'promotion_ids' => [$unqualified->id, $unqualified2->id, $signUpPromotion->id],
            'service_id' => $service->id,
            'user_id' => $user->id,
        ]);

        $order = Order::first();
        $this->assertNotNull($order);
        $this->assertEquals($service->price * $signUpPromotion->discount, $order->amount);
    }

    /** @test */
    public function it_can_create_order_when_product_id_is_present()
    {
        $this->withoutExceptionHandling();
        $quantity = 3;
        $user = User::factory()->create();
        $service = $this->getService();
        $signUpPromotion = $this->getPromotion();
        $product1 = Product::factory()->create(['price' => 20, 'stock' => 10]);
        $product2 = Product::factory()->create(['price' => 50, 'stock' => 10]);
        $this->assertDatabaseCount('order_products', 0);

        $this->createOrderWithMock([
            'promotion_ids' => [$signUpPromotion->id],
            'service_id' => $service->id,
            'user_id' => $user->id,
            'product_ids' => [
                ['id' => $product1->id, 'quantity' => $quantity],
                ['id' => $product2->id],
            ],
        ]);

        $order = Order::first();
        $discountedPrice = $service->price * $signUpPromotion->discount;
        $this->assertEquals($discountedPrice, $order->amount);
        $this->assertEquals(($product1->price * $quantity) + $product2->price + $discountedPrice, $order->total_amount);
        $this->assertEquals(($product1->price * $quantity) + $product2->price, $order->product_amount);
        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $product1->id,
            'quantity' => $quantity,
            'name' => $product1->name,
            'price' => $product1->price,
            'total_price' => $product1->price * $quantity,
        ]);

        $this->assertDatabaseHas('order_products', [
            'order_id' => $order->id,
            'product_id' => $product2->id,
            'quantity' => 1,
            'name' => $product2->name,
            'price' => $product2->price,
            'total_price' => $product2->price * 1,
        ]);

        $this->assertEquals($product1->stock - $quantity, $product1->fresh()->stock);
        $this->assertEquals($product2->stock - 1, $product2->fresh()->stock);
    }

    /** @test */
    public function its_default_payment_is_cash()
    {
        $signUpPromotion = $this->getPromotion();
        $service = $this->getService();
        $this->assertDatabaseCount('orders', 0);
        $this->createOrderWithMock([
            'promotion_ids' => [$signUpPromotion->id],
            'service_id' => $service->id,
            'user_id' => $this->customer()->id
        ]);

        $order = Order::first();
        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(OrderPayment::CASH->toLower(), $order->payment);
    }

    /** @test */
    public function it_can_create_order_via_gcash_payment()
    {
        $signUpPromotion = $this->getPromotion();
        $service = $this->getService();
        $this->assertDatabaseCount('orders', 0);
        $this->createOrderWithMock([
            'promotion_ids' => [$signUpPromotion->id],
            'service_id' => $service->id,
            'payment' => OrderPayment::GCASH->value,
            'user_id' => $this->customer()->id
        ]);

        $order = Order::first();
        $this->assertDatabaseCount('orders', 1);
        $this->assertEquals(OrderPayment::GCASH->toLower(), $order->payment);
    }

    private function orderAttributes(mixed $overwrites)
    {
        $attributes = Order::factory()->make()->toArray();
        $attributes['payment'] = OrderPayment::CASH->value;
        $attributes['type'] = OrderType::WALKIN->value;
        $attributes['promotion_ids'] = [1];
        return array_merge($attributes, $overwrites);
    }

}
