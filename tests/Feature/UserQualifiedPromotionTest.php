<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Promotion;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\OrderCanBeCreated;
use Tests\TestCase;

class UserQualifiedPromotionTest extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderCanBeCreated;

    public function __construct(string $name)
    {
        parent::__construct($name);
        $this->endpoint = 'api/admin/user/qualified-promotion';
    }

    /** @test */
    public function it_can_check_what_promotions_a_user_is_qualify(): void
    {
        $signUpDiscountPromotion = $this->getPromotion('sign up promotion', 'App\\Models\\Promotions\\SignUpDiscount',
            true);
        $wednesdayPromotion = $this->getWednesdayPromotion();
        $rewardCertificate = $this->rewardGiftCertificatePromotion();
        $service = $this->getService();
        $user = User::factory()->create();

        $response = $this->signInAsAdmin()->getJson($this->endpoint . "/{$user->id}/{$service->id}");

        $body = $response->json();
        $response->assertStatus(200);
        $this->assertArrayHasKey('isolated', $body);
        $this->assertArrayHasKey('non-isolated', $body);
        $this->assertTrue(in_array(
            $signUpDiscountPromotion->id,
            array_column($body['isolated'], 'id')
        ));
        $this->assertTrue(in_array(
            $wednesdayPromotion->id,
            array_column($body['non-isolated'], 'id')
        ));
        $this->assertTrue(in_array(
            $rewardCertificate->id,
            array_column($body['non-isolated'], 'id')
        ));
    }

    /** @test */
    public function only_necessary_attribute_is_return_to_front_end(): void
    {
        $this->getPromotion();
        $response = $this->getQualifiedPromotions();

        $keys = array_keys($response->json()['non-isolated'][0]);
        $this->assertSame(['id', 'name', 'status', 'start', 'until', 'isolated'], $keys);
    }

    /** @test */
    public function if_promotion_class_not_implement_it_should_gets_503(): void
    {
        $this->getPromotion('foo', 'App\\Foo');
        $response = $this->getQualifiedPromotions();

        $message = $response->assertServiceUnavailable()->json('message');
        $this->assertEquals('promotion is not implemented', $message);
    }

    /** @test */
    public function nothing_is_return_if_unavailable_promotions_is_found(): void
    {
        $disabledPromotion = Promotion::factory()->create(['status' => false]);
        $expiredPromotion = Promotion::factory()->create(['until' => now()->subDays()]);
        $notStartPromotion = Promotion::factory()->create(['start' => now()->addDays(3)]);

        $response = $this->getQualifiedPromotions();

        $this->assertEmpty($response->json('isolated'));
        $this->assertEmpty($response->json('non-isolated'));
    }

    /** @test */
    public function it_only_return_the_qualified_promotion(): void
    {
        $user = Order::factory()->create()->user;
        $qualifyPromotion = $this->getWednesdayPromotion();
        $disqualifiedPromotion = $this->getPromotion();
        $qualifyPromotion2 = $this->rewardGiftCertificatePromotion(true);

        $response = $this->getQualifiedPromotions($user);

        $body = $response->json();
        $this->assertTrue(!in_array(
            $disqualifiedPromotion->id,
            array_column($body['non-isolated'], 'id')
        ));
        $this->assertTrue(in_array(
            $qualifyPromotion->id,
            array_column($body['non-isolated'], 'id')
        ));
        $this->assertTrue(in_array(
            $qualifyPromotion2->id,
            array_column($body['isolated'], 'id')
        ));
    }

    /** @test */
    public function only_admin_can_access()
    {
        $customer = User::factory()->create(['type' => 0]);
        $this->actingAs($customer);

        $service = $this->getService();
        $response = $this->getJson($this->endpoint . "/{$customer->id}/{$service->id}");
        $response->assertForbidden();
    }

    /** @test */
    public function only_logged_in_user_can_access()
    {
        $this->getJson($this->endpoint . "/8/9")->assertUnauthorized();
    }

    /** @test */
    public function unverified_user_gets_404(): void
    {
        $unverifiedUser = User::factory()->create(['phone_verified_at' => null]);
        $service = $this->getService();
        $this->signInAsAdmin()->getJson($this->endpoint."/{$unverifiedUser->id}/{$service->id}")->assertNotFound();
    }

    public function getQualifiedPromotions($user = null)
    {
        $service = $this->getService();
        $user ??= User::factory()->create();
        return $this->signInAsAdmin()->getJson($this->endpoint . "/{$user->id}/{$service->id}");
    }
}
