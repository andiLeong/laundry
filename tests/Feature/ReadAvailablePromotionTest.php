<?php

namespace Tests\Feature;

use App\Models\Promotion;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadAvailablePromotionTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_can_only_get_available_promotions()
    {
        $promotion = Promotion::factory()->create();
        $promotion2 = Promotion::factory()->create();
        $expiredPromotion = Promotion::factory()->create([
            'until' => today()->subDays(7)
        ]);
        $disabledPromotion = Promotion::factory()->create([
            'status' => 0,
        ]);
        $notStartedPromotion = Promotion::factory()->create([
            'start' => now()->addDay(),
        ]);
        $response = $this->getJson('/api/available-promotion');
        $ids = array_column($response->json(),'id');

        $this->assertTrue(in_array($promotion->id, $ids));
        $this->assertTrue(in_array($promotion2->id, $ids));
        $this->assertFalse(in_array($expiredPromotion->id, $ids));
        $this->assertFalse(in_array($disabledPromotion->id, $ids));
        $this->assertFalse(in_array($notStartedPromotion->id, $ids));
    }

    /** @test */
    public function it_cant_get_sensitive_column()
    {
        $promotion = Promotion::factory()->create(['name' => 'signup']);
        $response = $this->getJson('/api/available-promotion')->json()[0];

        $this->assertEquals($response['name'],$promotion->name);
        $this->assertArrayNotHasKey('class', $response);
    }
}
