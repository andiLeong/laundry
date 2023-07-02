<?php

namespace Tests\Feature;

use App\Models\Promotion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ReadPromotionTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $endpoint = '/api/promotion';

    /** @test */
    public function it_can_only_get_available_promotions()
    {
        $promotion = Promotion::factory()->create();
        $promotion2 = Promotion::factory()->create();
        $disabledPromotion = Promotion::factory()->create([
            'status' => 0,
        ]);

        $response = $this->getJson($this->endpoint);
        $ids = array_column($response->json(), 'id');

        $this->assertTrue(in_array($promotion->id, $ids));
        $this->assertTrue(in_array($promotion2->id, $ids));
        $this->assertFalse(in_array($disabledPromotion->id, $ids));
        Cache::flush();
    }

    /** @test */
    public function it_cant_get_sensitive_column()
    {
        $promotion = Promotion::factory()->create(['name' => 'signup']);
        $response = $this->getJson($this->endpoint)->json()[0];

        $this->assertEquals($response['name'], $promotion->name);
        $this->assertArrayNotHasKey('class', $response);
        Cache::flush();
    }

    /** @test */
    public function get_promotions_result_is_cached()
    {
        $promotion = Promotion::factory()->create();
        $this->getJson($this->endpoint);

        $promotion2 = Promotion::factory()->create();
        $names = $this->getJson($this->endpoint)->collect()->pluck('name');
        $this->assertTrue($names->contains($promotion->name));
        $this->assertFalse($names->contains($promotion2->name));
        Cache::flush();
    }

    /** @test */
    public function it_can_get_a_single_available_promotion_detail()
    {
        $promotion = Promotion::factory()->create();
        $disabledPromotion = Promotion::factory()->create([
            'status' => 0,
        ]);

        $name = $this
            ->getJson($this->getSinglePromotionEndpoint($promotion->id))
            ->assertOk()
            ->json('name');

        $this
            ->getJson($this->getSinglePromotionEndpoint($disabledPromotion->id))
            ->assertNotFound();

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
