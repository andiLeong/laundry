<?php

namespace Tests\Feature;

use App\Models\Product;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadProductTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $endpoint = '/api/product';

    /** @test */
    public function it_can_get_all_products_details()
    {
        $product = Product::factory()->create(['name' => 'downny detergent']);
        $response = $this->getJson($this->endpoint)->assertSuccessful()->json('data')[0];

        $this->assertEquals($response['name'], $product->name);
    }
}
