<?php

namespace Tests\Feature;

use App\Models\Address;
use Tests\TestCase;

class DeleteAddressTest extends TestCase
{
//    /** @test */
    public function it_can_be_deleted(): void
    {
        $address = Address::factory()->create();
        $this->signIn($address->user)->deleteJson('/api/address/' . $address->id)->assertSuccessful();
    }

//    /** @test */
    public function only_login_user_can_delete()
    {
        $this->deleteJson('/api/address/9988')->assertUnauthorized();
    }

//    /** @test */
    public function it_can_only_be_deleted_by_its_owner()
    {
        $address = Address::factory()->create();
        $response = $this->signIn()->deleteJson('/api/address/' . $address->id);
        $response->assertForbidden();
    }

//    /** @test */
    public function it_gets_404_not_found(): void
    {
        $this->signIn()->deleteJson('/api/address/'. 99999)->assertNotFound();
    }
}
