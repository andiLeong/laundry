<?php

namespace Tests\Feature;

use App\Models\Promotion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadPromotionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $endpoint = '/api/promotion';

    /** @test */
    public function it_can_get_promotions()
    {
        $promotion = Promotion::factory()->create(['name' => 'signup']);
        $response = $this->getJson($this->endpoint)->json('data')[0];

        $this->assertEquals($promotion->name,$response['name']);
        $this->assertEquals($promotion->slug,$response['slug']);
        $this->assertEquals($promotion->description,$response['description']);
        $this->assertEquals($promotion->image,$response['image']);
        $this->assertEquals($promotion->thumbnail,$response['thumbnail']);
        $this->assertColumnsSame(['name','slug','description','image','thumbnail'],array_keys($response));
    }

    /** @test */
    public function it_cant_get_disabled_promotions()
    {
        $promotion = Promotion::factory()->create(['name' => 'signup']);
        $promotion2 = Promotion::factory()->create(['name' => 'disabled', 'status' => false]);
        $response = $this->getJson($this->endpoint)->collect('data')->pluck('name');

        $this->assertTrue(in_array($promotion->name, $response->all()));
        $this->assertFalse(in_array($promotion2->name, $response->all()));
    }

    /** @test */
    public function it_can_be_filtered_by_name()
    {
        $foo = Promotion::factory()->create(['name' => 'foo']);
        Promotion::factory()->create(['name' => 'bar']);

        $response = $this->getJson($this->endpoint . '?name=fo')->json('data')[0];
        $this->assertEquals($response['name'], $foo->name);
        $this->assertArrayNotHasKey('class', $response);
    }

    /** @test */
    public function it_can_get_a_single_available_promotion_detail()
    {
        $promotion = Promotion::factory()->create();
        $response = $this->getJson($this->getSinglePromotionEndpoint($promotion->slug))->json();

        $this->assertEquals($promotion->name,$response['name']);
        $this->assertEquals($promotion->slug,$response['slug']);
        $this->assertEquals($promotion->description,$response['description']);
        $this->assertEquals($promotion->discount,$response['discount']);
        $this->assertEquals($promotion->isolated,$response['isolated']);
        $this->assertEquals($promotion->start->toJson(),$response['start']);
        $this->assertEquals($promotion->until->toJson(),$response['until']);
        $this->assertEquals($promotion->image,$response['image']);

        $this->assertColumnsSame(['name','slug','description','discount','isolated','start','until','image'],array_keys($response));
    }

    /** @test */
    public function it_get_404_if_promotion_not_exists()
    {
        $message = $this
            ->getJson($this->endpoint . '/' . 99999999)
            ->assertNotFound()
            ->json('message');

        $this->assertEquals('Promotion not found', $message);
    }

    /** @test */
    public function it_get_404_if_promotion_is_not_enabled()
    {
        $promotion = Promotion::factory()->create(['name' => 'disabled', 'status' => false]);
        $message = $this
            ->getJson($this->endpoint . '/' . $promotion->slug)
            ->assertNotFound()
            ->json('message');

        $this->assertEquals('Promotion not found', $message);
    }

    public function getSinglePromotionEndpoint($id)
    {
        return $this->endpoint . '/' . $id;
    }
}
