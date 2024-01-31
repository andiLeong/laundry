<?php

namespace Tests\Unit;

use App\Models\OrderImage;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class OrderImageTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_has_its_creator(): void
    {
        $creator = User::factory()->create();
        $image = OrderImage::factory()->create(['uploaded_by' => $creator->id]);
        $this->assertEquals($image->creator->first_name, $creator->first_name);
    }
}
