<?php

namespace Tests\Unit;

use App\Models\Service;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_contains_all_necessary_information(): void
    {
        $service = Service::factory()->create([
            'name' => 'wash and dry full service',
            'description' => 'wash and dry',
            'price' => 200,
        ]);

        $this->assertEquals(200, $service->price);
        $this->assertEquals('wash and dry full service', $service->name);
        $this->assertEquals('wash and dry', $service->description);
    }

    /** @test */
    public function it_can_determine_if_a_service_is_full(): void
    {
        $service = Service::factory()->create();
        $service2 = Service::factory()->create(['full_service' => false]);

        $this->assertTrue($service->isFull());
        $this->assertFalse($service2->isFull());
    }
}
