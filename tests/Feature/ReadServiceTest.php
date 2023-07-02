<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class ReadServiceTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_can_get_list_of_service(): void
    {
        $wash = Service::factory()->create(['name' => 'wash']);
        $dry = Service::factory()->create(['name' => 'dry']);
        $full = Service::factory()->create(['name' => 'full service']);
        $body = $this->getJson('/api/service')
            ->assertStatus(200)
            ->collect()
            ->pluck('name');

        $this->assertTrue($body->contains($wash->name));
        $this->assertTrue($body->contains($dry->name));
        $this->assertTrue($body->contains($full->name));
        $this->assertfalse($body->contains('wash + dry'));
    }

    /** @test */
    public function service_is_cached(): void
    {
        $full = Service::factory()->create(['name' => 'full service']);
        $this->getJson('/api/service');

        $new = Service::factory()->create(['name' => 'new']);

        $body = $this->getJson('/api/service')
            ->collect()
            ->pluck('name');

        $this->assertFalse($body->contains($new->name));
        $this->assertTrue($body->contains($full->name));
    }
}
