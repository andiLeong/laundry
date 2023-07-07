<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminShowUserTest extends TestCase
{
    use LazilyRefreshDatabase;

    private string $endpoint = 'api/admin/user';
    protected string $phone = '09060184499';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create(['first_name' => 'kate', 'phone' => $this->phone, 'type' => UserType::employee->value]);
    }

    /** @test */
    public function unverified_user_gets_404(): void
    {
        $unverifiedUser = User::factory()->create(['phone' => '0934342322', 'phone_verified_at' => null]);
        $user = $this->fetch(null,$unverifiedUser->phone)->assertNotFound();
    }

    /** @test */
    public function sensitive_column_not_hidden(): void
    {
        $user = $this->fetch();
        $this->assertArrayNotHasKey('password', $user);
    }

    /** @test */
    public function only_login_user_can_access()
    {
        $this->getJson($this->endpoint)->assertUnauthorized();
    }

    /** @test */
    public function get_404_if_a_user_is_not_found()
    {
        $this->fetch(null, '09272714878')->assertNotFound();
    }

    protected function fetch($as = null, $phone = null)
    {
        $as ??= $this->user;
        $phone ??= $this->phone;
        return $this->signIn($as)->getJson($this->endpoint . '/' . $phone);
    }
}
