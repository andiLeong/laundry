<?php

namespace Tests\Unit;


use App\Models\Order;
use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class SignUpDiscountTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function only_user_that_dose_not_make_any_order_can_avail(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $signUpPromotion = Promotion::factory()->create([
            'class' => 'App\\Models\\Promotions\\SignUpDiscount',
        ]);
        $signUp = new $signUpPromotion['class']($user,$service,$signUpPromotion);

        $this->assertEquals(0, Order::where('user_id', $user->id)->count());
        $this->assertTrue($signUp->qualify());
    }

    /** @test */
    public function if_user_has_made_order_before_then_can_not_avail_this_promotion()
    {
        $order = Order::factory()->create();
        $service = Service::factory()->create();
        $user = $order->user;
        $signUpPromotion = Promotion::factory()->create([
            'class' => 'App\\Models\\Promotions\\SignUpDiscount',
        ]);
        $signUp = new $signUpPromotion['class']($user,$service,$signUpPromotion);

        $this->assertEquals(1, Order::where('user_id', $user->id)->count());
        $this->assertFalse($signUp->qualify());
    }
}
