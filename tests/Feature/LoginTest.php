<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;
use Tests\UserCanBeVerified;

class LoginTest extends TestCase
{
    use LazilyRefreshDatabase;
    use UserCanBeVerified;

    protected $endpoint = 'api/login';

    protected function setUp(): void
    {
        parent::setUp();
        $this->phone = '09081187899';
    }

    /** @test */
    public function unverified_user_can_not_login(): void
    {
        $this->setUnverifiedUser();
        $response = $this->postJson($this->endpoint, [
            'phone' => $this->user->phone,
            'password' => 'password',
        ]);

        $this->assertValidateMessage('Please verify your number before login', $response, 'phone');
    }

    /** @test */
    public function logged_in_user_cant_login(): void
    {
        $this->signIn()
            ->postJson($this->endpoint)->assertForbidden();
    }

    /** @test */
    public function wrong_credential_can_not_login(): void
    {
        $this->setVerifiedUser();
        $this->user->password = 'password';
        $this->user->save();

        $response = $this->postJson($this->endpoint, [
            'phone' => $this->user->phone,
            'password' => 'wrong-password',
        ]);

        $this->assertValidateMessage('Login Failed, please check your credential', $response, 'phone');
    }

    /** @test */
    public function it_gets_validation_error_if_phone_is_not_found(): void
    {
        $response = $this->postJson($this->endpoint, [
            'phone' => '09051457866'
        ]);

        $this->assertValidateMessage('Login Failed, please check your number', $response, 'phone');
    }

    /** @test */
    public function it_can_sign_in()
    {
        $this->assertFalse(Auth::check());

        $this->setVerifiedUser();
        $this->user->password = 'password';
        $this->user->save();

        $this->postJson($this->endpoint, [
            'phone' => $this->user->phone,
            'password' => 'password',
        ]);
        $this->assertTrue(Auth::check());
        $this->assertEquals($this->user->id, Auth::id());
    }
}
