<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class AdminUserUpdateProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    private $endpoint = '/api/admin/user/profile';
    private $phone;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = $this->admin();
        $this->phone = $this->user->phone;
    }

    /** @test */
    public function it_can_update_user_basic_information(): void
    {
        $user = $this->user;
        $this->update(['first_name' => 'new'])->assertStatus(200);

        $this->assertEquals('new', $user->fresh()->first_name);
    }

    /** @test */
    public function it_can_perform_update_if_you_not_sign_in(): void
    {
         $this->patchJson($this->endpoint, [])->assertUnauthorized();
    }

    /** @test */
    public function only_staff_or_admin_can_update(): void
    {
        $user = User::factory()->create();
        $this->be($user)->patchJson($this->endpoint, [])->assertForbidden();
    }

    /** @test */
    public function first_name_must_valid(): void
    {
        $name = 'first_name';
        $rule = ['required', 'string', 'max:50'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->update($payload)
        );
    }

    /** @test */
    public function last_name_must_valid(): void
    {
        $name = 'last_name';
        $rule = ['required', 'string', 'max:50'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->update($payload)
        );
    }

    /** @test */
    public function middle_name_must_valid(): void
    {
        $name = 'middle_name';
        $rule = ['nullable', 'string', 'max:50'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->update($payload)
        );
    }

    public function update($overwrites = [])
    {
        $payload = $this->userAttributes($overwrites);
        return $this->signInAsAdmin($this->user)->patchJson($this->endpoint, $payload);
    }

    private function userAttributes(mixed $overwrites)
    {
        $attributes = User::factory()->make()->toArray();
        unset($attributes['type']);
        return array_merge($attributes, $overwrites);
    }
}
