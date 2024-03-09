<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Place;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\AdminAuthorization;
use Tests\TestCase;

class AdminReadPlaceTest extends TestCase
{
    use LazilyRefreshDatabase;
    use AdminAuthorization;

    protected $endpoint = '/api/admin/place';

    protected function setUp(): void
    {
        parent::setUp(); // TODO: Change the autogenerated stub
        $this->method = 'getJson';
        $this->places = Place::factory(2)->create();
        $this->branch = Branch::factory()->create();
    }

    /** @test */
    public function it_can_read_places_data(): void
    {
        $ids = $this->fetch()->assertOk()->collect('data')->pluck('id');

        $this->assertTrue($ids->contains($this->places[0]->id));
        $this->assertTrue($ids->contains($this->places[1]->id));
    }

    /** @test */
    public function it_can_order_by_id(): void
    {
        $places = $this->fetch()->json('data');
        $place = Place::all();

        $this->assertEquals($place->last()['id'], $places[0]['id']);
        $this->assertEquals($place->last()['id'] - 1, $places[1]['id']);
    }

    /** @test */
    public function it_can_search_by_name(): void
    {
        $place = Place::factory()->create(['name' => 'foo']);
        $places = $this->fetch(['name' => 'foo'])->json('data');
        $this->assertEquals($place->name, $places[0]['name']);
    }

    /** @test */
    public function delivery_fee_is_being_return(): void
    {
        $places = $this->fetch()->assertStatus(200)->json('data');
        $this->assertArrayHasKey('delivery_fee', $places[0]);
    }

    protected function fetch($query = [], $as = null)
    {
        return $this->fetchAsAdmin($query, $as);
    }
}
