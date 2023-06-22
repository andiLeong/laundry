<?php

namespace Tests;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected $user;

    use CreatesApplication;

    public function signInAsAdmin($admin = null)
    {
        $admin = $admin ?? User::factory()->create([
            'type' => UserType::admin->value,
        ]);
        $this->actingAs($this->user = $admin);

        return $this;
    }

    public function signIn($user = null)
    {
        $user = $user ?? User::factory()->create();
        $this->actingAs($this->user = $user);
        return $this;
    }
}
