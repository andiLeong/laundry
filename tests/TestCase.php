<?php

namespace Tests;

use App\Models\Enum\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $user;

    use CreatesApplication;

    public function admin()
    {
        return User::factory()->create([
            'type' => UserType::ADMIN->value,
        ]);
    }

    public function staff($attributes = [])
    {
        return User::factory()->create(array_merge([
            'type' => UserType::EMPLOYEE->value,
        ], $attributes));
    }

    public function customer($attributes = [])
    {
        return User::factory()->create(array_merge([
            'type' => UserType::CUSTOMER->value,
        ], $attributes));
    }

    public function signInAsAdmin($admin = null)
    {
        $admin = $admin ?? $this->admin();
        $this->actingAs($this->user = $admin);

        return $this;
    }

    public function signIn($user = null)
    {
        $user = $user ?? User::factory()->create();
        $this->actingAs($this->user = $user);
        return $this;
    }

    protected function sanctumLogIn($phone, $password = 'password', $referer = 'localhost:3000'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('api/login', [
            'phone' => $phone,
            'password' => $password,
        ], ['referer' => $referer]);
    }

    protected function sanctumLogOut($phone, $password = 'password', $referer = 'localhost:3000'): \Illuminate\Testing\TestResponse
    {
        return $this->postJson('api/logout', [
            'phone' => $phone,
            'password' => $password,
        ], ['referer' => $referer]);
    }

    public function assertValidateMessage($message, $response, $key): static
    {
        $response->assertJsonValidationErrorFor($key);
        $response->assertStatus(422);
        $this->assertTrue(in_array(
            $message,
            $response->collect('errors')->get($key)
        ));
        return $this;
    }

    public function assertColumnsSame($columns, $result)
    {
        sort($columns);
        sort($result);

        $this->assertEquals($columns, $result);
    }

    protected function performFetch($query = [], $user = null, $endpoint = null)
    {
        $endpoint ??= $this->endpoint;
        $query = '?' . http_build_query($query);
        return $this
            ->signIn($user)
            ->getJson($endpoint . $query);
    }

    public function fetchAsAdmin($query = [], $user = null, $endpoint = null)
    {
        $user ??= $this->admin();
        return $this->performFetch($query, $user, $endpoint);
    }

    public function fetchAsStaff($query = [], $user = null, $endpoint = null)
    {
        $user ??= $this->staff();
        return $this->performFetch($query, $user, $endpoint);
    }
}
