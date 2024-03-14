<?php

namespace Tests\Feature;

use App\Http\Validation\AddressValidation;
use App\Models\Address;
use App\Models\GooglePlaces;
use App\Models\Place;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\Validate;

class CreateAddressTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $placeId = 'google-place-id';

    /** @test */
    public function user_can_create_address(): void
    {
        $this->mock(AddressValidation::class, function (MockInterface $mock) {
            $mock->shouldReceive('validate')->once()->andReturn(true);
            $mock->shouldReceive('getPlace')->once()->andReturn(Place::factory()->create([
                'place_id' => $this->placeId
            ]));
        });

        $this->assertDatabaseCount('addresses', 0);
        $response = $this->createAddress();

        $address = Address::first();
        $place = Place::where('place_id', $this->placeId)->first();
        $this->assertDatabaseCount('addresses', 1);
        $this->assertEquals($this->user->id, $address->user_id);
        $this->assertEquals($place->id, $address->place_id);
        $response->assertSuccessful();
    }

    /** @test */
    public function only_authenticated_user_can_create_address(): void
    {
        $this->postJson('/api/address')->assertUnauthorized();
    }

    /** @test */
    public function room_must_valid()
    {
        $this->mock(AddressValidation::class, function (MockInterface $mock) {
            $mock->shouldReceive('validate')->andReturn(true);
        });

        $name = 'room';
        $rule = ['nullable', 'string', 'max:100'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createAddress($payload)
        );
    }

    /** @test */
    public function if_place_is_not_in_service_country_gets_validation_exception(): void
    {
        $this->mock(AddressValidation::class, function (MockInterface $mock) {
            $mock->shouldReceive('validate')->once()->andThrow(new \Exception('place is not in the service country'));
        });
        $response = $this->createAddress();

        $this->assertValidateMessage('place is not in the service country', $response, 'place_id');
    }

    /** @test */
    public function if_place_is_not_in_service_city_gets_validation_exception(): void
    {
        $this->mock(AddressValidation::class, function (MockInterface $mock) {
            $mock->shouldReceive('validate')->once()->andThrow(new \Exception('place is not in the service city'));
        });
        $response = $this->createAddress();

        $this->assertValidateMessage('place is not in the service city', $response, 'place_id');
    }

    /** @test */
    public function if_place_is_not_in_service_province_gets_validation_exception(): void
    {
        $this->mock(AddressValidation::class, function (MockInterface $mock) {
            $mock->shouldReceive('validate')->once()->andThrow(new \Exception('place is not in the service province'));
        });
        $response = $this->createAddress();

        $this->assertValidateMessage('place is not in the service province', $response, 'place_id');
    }

    /** @test */
    public function if_place_is_far_away_from_branch_gets_validation_exception(): void
    {
        $this->mock(AddressValidation::class, function (MockInterface $mock) {
            $mock->shouldReceive('validate')->once()->andThrow(new \Exception('The place seem like too far way from our branch'));
        });
        $response = $this->createAddress();

        $this->assertValidateMessage('The place seem like too far way from our branch', $response, 'place_id');
    }

    /** @test */
    public function it_gets_validation_exception_if_google_api_return_error(): void
    {
        $this->mock(GooglePlaces::class, function (MockInterface $mock) {
            $mock->shouldReceive('get')->once()->andThrow(new \Exception('Error on getting place, please contact support!'));
        });
        $response = $this->createAddress();

        $this->assertValidateMessage('Error on getting place, please contact support!', $response, 'place_id');
    }

    /** @test */
    public function if_the_place_id_is_existed_before_no_validation_throw(): void
    {
        $this->assertDatabaseCount('addresses', 0);
        $place = Place::factory()->create([
            'place_id' => $this->placeId,
            'id' => 100,
        ]);

        $this->createAddress();
        $address = Address::first();

        $this->assertNotNull($address);
        $this->assertEquals($address->place_id, $place->id);
    }

    public function createAddress(array $overwrites = [])
    {
        return $this->signIn()->postJson('/api/address',
            $this->addressAttributes($overwrites)
        );
    }

    private function addressAttributes(mixed $overwrites)
    {
        $attributes = Address::factory()->make()->toArray();
        $attributes['place_id'] = $this->placeId;
        return array_merge($attributes, $overwrites);
    }
}
