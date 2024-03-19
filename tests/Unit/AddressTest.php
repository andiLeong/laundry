<?php

namespace Tests\Unit;

use App\Models\Address;
use App\Models\Place;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AddressTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_contains_all_necessary_information(): void
    {
        $address = new Address();
        $address->name = 'red residence';
        $address->street = 'abc str';
        $address->number = '3269';
        $address->city = 'manila';
        $address->province = 'metro manila';
        $address->user_id = 100;

        $this->assertEquals('abc str', $address->street);
        $this->assertEquals('3269', $address->number);
        $this->assertEquals('manila', $address->city);
        $this->assertEquals(100, $address->user_id);
        $this->assertEquals('red residence', $address->name);
        $this->assertEquals('metro manila', $address->province);
    }

    /** @test */
    public function it_belongs_to_an_user()
    {
        $user = User::factory()->create();
        $address = Address::factory()->create(['user_id' => $user->id]);

        $this->assertEquals($user->id, $address->user->id);
    }

    /** @test */
    public function it_has_a_place()
    {
        $address = Address::factory()->create();
        $place = Place::find($address->place_id);

        $this->assertEquals($place->id, $address->place->id);
    }
}
