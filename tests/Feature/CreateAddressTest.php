<?php

namespace Tests\Feature;

use App\Http\Validation\AddressValidation;
use App\Models\Address;
use App\Models\Place;
use App\Notification\Telegram;
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
    public function name_must_valid()
    {
        $this->mock(AddressValidation::class, function (MockInterface $mock) {
            $mock->shouldReceive('validate')->andReturn(true);
        });

        $name = 'room';
        $rule = ['nullable','string','max:100'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createAddress($payload)
        );
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
