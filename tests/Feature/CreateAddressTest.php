<?php

namespace Tests\Feature;

use App\Models\Address;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class CreateAddressTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function user_can_create_address(): void
    {
        $this->assertDatabaseCount('addresses', 0);
        $response = $this->createAddress();

        $this->assertDatabaseCount('addresses', 1);
        $this->assertEquals($this->user->id, Address::first()->user_id);
        $response->assertSuccessful();
    }

    /** @test */
    public function only_authenticated_user_can_create_address(): void
    {
        $this->postJson('/api/address')->assertUnauthorized();
    }

    /** @test */
    public function city_must_valid()
    {
        $name = 'city';
        $rule = ['required','string','max:100'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createAddress($payload)
        );
    }

    /** @test */
    public function number_must_valid()
    {
        $name = 'number';
        $rule = ['required','string','max:100'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createAddress($payload)
        );
    }

    /** @test */
    public function province_must_valid()
    {
        $name = 'province';
        $rule = ['required','string','max:100'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createAddress($payload)
        );
    }

    /** @test */
    public function street_must_valid()
    {
        $name = 'street';
        $rule = ['required','string','max:255'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->createAddress($payload)
        );
    }

    /** @test */
    public function name_must_valid()
    {
        $name = 'name';
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
        return array_merge($attributes, $overwrites);
    }
}
