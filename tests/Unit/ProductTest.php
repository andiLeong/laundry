<?php

namespace Tests\Unit;

use App\Models\Product;
use PHPUnit\Framework\TestCase;

class ProductTest extends TestCase
{

    /** @test */
    public function it_contains_all_necessary_information(): void
    {
        $product = new Product();
        $product->name = 'downy detergent';
        $product->price = 58;
        $product->stock = 100;

        $this->assertEquals(100, $product->stock);
        $this->assertEquals(58, $product->price);
        $this->assertEquals('downy detergent', $product->name);
    }
}
