<?php

namespace Tests\Feature;

use App\Models\Address;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class UpdateAddressTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_can_update_address_by_authenticated_user(): void
    {
        $address = Address::factory()->create();
        $response = $this->updateAddress(['room' => '2748'],$address);

        $this->assertEquals('2748', $address->refresh()->room);
        $response->assertStatus(200);
    }

    /** @test */
    public function only_authenticated_user_can_perform_update()
    {
        $this->patchJson('/api/address/'. 999)->assertUnauthorized();
    }

    /** @test */
    public function it_gets_404_not_found(): void
    {
        $this->signIn()->patchJson('/api/address/'. 99999)->assertNotFound();
    }

    /** @test */
    public function update_room_must_valid()
    {
        $name = 'room';
        $rule = ['required','string','max:100'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->updateAddress($payload)
        );
    }

    public function updateAddress(array $overwrites = [], $address = null)
    {
        $address ??= Address::factory()->create();
        return $this->signIn()->patchJson('/api/address/'. $address->id,
            $this->addressAttributes($overwrites)
        );
    }

    private function addressAttributes(mixed $overwrites)
    {
        $attributes = Address::factory()->make()->toArray();
        return array_merge($attributes, $overwrites);
    }
}
