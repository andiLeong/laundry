<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LogoutTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected $endpoint = 'api/logout';

    /** @test */
    public function it_can_logout(): void
    {
        $admin = $this->admin();
        $this->be($admin)->assertAuthenticatedAs($admin);
        $this->sanctumLogOut($admin->phone)->assertOk();
        $this->assertGuest('web');
    }

    /** @test */
    public function only_auth_user_can_logout(): void
    {
        $this->sanctumLogOut('09060114789')->assertUnauthorized();
    }
}
