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
    public function it_can_get_promotion_details_but_exclude_sensitive_column()
    {
        $promotion = Promotion::factory()->create(['name' => 'signup']);
        $response = $this->getJson($this->endpoint)->json('data')[0];

        $this->assertEquals($response['name'], $promotion->name);
        $this->assertEquals($response['image'], $promotion->image);
        $this->assertEquals($response['thumbnail'], $promotion->thumbnail);
        $this->assertArrayNotHasKey('class', $response);
    }

    /** @test */
    public function promotion_can_be_filtered_by_name()
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

        $name = $this
            ->getJson($this->getSinglePromotionEndpoint($promotion->id))
            ->assertOk()
            ->json('name');

        $this
            ->getJson($this->endpoint . '/' . 99999999)
            ->assertNotFound();

        $this->assertEquals($promotion->name, $name);
    }

    /** @test */
    public function sensitive_column_is_hidden_from_read_single_promotion()
    {
        $promotion = Promotion::factory()->create();
        $response = $this->getJson($this->getSinglePromotionEndpoint($promotion->id))->json();
        $this->assertArrayNotHasKey('class', $response);
    }

    public function getSinglePromotionEndpoint($id)
    {
        return $this->endpoint . '/' . $id;
    }
}
