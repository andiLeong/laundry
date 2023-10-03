<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class UserUpdatePasswordTest extends TestCase
{
    use LazilyRefreshDatabase;

    private $endpoint = '/api/user/password';

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create([
            'password' => 'foo'
        ]);
    }

    /** @test */
    public function it_can_update_user_password(): void
    {
        $newPassword = 'iamanewpassword123';
        $oldPassword = $this->user->password;
        $this->update([
            'password' => $newPassword,
            'password_confirmation' => $newPassword
        ])->assertStatus(200);

        $this->assertNotEquals($newPassword, $this->user->fresh()->password);
        $this->assertNotEquals($oldPassword, $this->user->fresh()->password);
        $this->assertTrue(password_verify($newPassword, $this->user->fresh()->password));
    }

    /** @test */
    public function only_auth_user_can_perform_update(): void
    {
        $this->patchJson($this->endpoint, [])->assertUnauthorized();
    }

    /** @test */
    public function password_must_valid(): void
    {
        $name = 'password';
        $rule = ['required', 'string', 'max:90','min:8'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->update($payload)
        );
    }

    /** @test */
    public function password_must_confirmed(): void
    {
        $newPassword = 'iamanewpassword123';
        $this->update([
            'password' => $newPassword,
            'password_confirmation' => 'foo'
        ])->assertStatus(422);
    }

    public function update($attributes = [])
    {
        return $this->signIn($this->user)->patchJson($this->endpoint, $attributes);
    }
}
