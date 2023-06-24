<?php

namespace Tests\Feature;

use App\Models\Address;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadAddressTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_can_read_all_the_address_of_an_user(): void
    {
        $this->signIn();
        $address = Address::factory(2)->create(['user_id' => $this->user->id]);
        $address2 = Address::factory()->create();
        $response = $this->getJson('/api/address');
        $addressIds = array_column(json_decode($response->getContent()), 'id');

        $this->assertTrue(in_array($address[0]['id'], $addressIds));
        $this->assertTrue(in_array($address[1]['id'], $addressIds));
        $this->assertNotTrue(in_array($address2->id, $addressIds));
        $response->assertStatus(200);
    }

    /** @test */
    public function only_authenticated_user_can_view_their_address(): void
    {
        $response = $this->getJson('/api/address');
        $response->assertStatus(401);
    }
}
