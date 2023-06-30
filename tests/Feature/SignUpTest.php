<?php

namespace Tests\Feature;

use App\Models\Sms\Contract\Sms;
use App\Models\Sms\Template;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\Validate;

class SignUpTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = '/api/signup';

    /** @test */
    public function it_can_sign_up_user(): void
    {
        $response = $this->signup();
        $user = User::find($response->json('id'));
        $this->assertNotNull($user);
        $response->assertSuccessful();
    }

    /** @test */
    public function after_signup_phone_still_not_been_verified(): void
    {
        $response = $this->signup();
        $user = User::find($response->json('id'));
        $this->assertFalse($user->isVerified());
    }

    /** @test */
    public function after_signup_user_type_is_customer(): void
    {
        $response = $this->signup();
        $user = User::find($response->json('id'));
        $this->assertTrue($user->isCustomer());
    }

    /** @test */
    public function after_signup_an_user_object_is_return_but_exclude_sensitive_information(): void
    {
        $response = $this->signup([
            'password' => 'new pass'
        ]);
        $this->assertNull($response->json('password'));
    }

    /** @test */
    public function after_signup_a_verification_is_recorded(): void
    {
        $this->assertDatabaseCount('verification_tokens', 0);
        $response = $this->signup();
        $user = User::find($response->json('id'));
        $verification = $user->verification;
        $mins = $verification->expired_at->diffInMinutes(now());

        $this->assertTrue($mins <= 5);
        $this->assertFalse($mins > 5);
        $this->assertNotNull($user->verification->token);
    }

    /** @test */
    public function after_signup_sms_is_sent_to_user_contains_the_token(): void
    {
        $this->mock(Sms::class, function($mock){
            return $mock->shouldReceive('send')->once()->andReturn(true);
        });
        $this->signup();
    }

    /** @test */
    public function verification_sms_must_send_to_the_correct_number_and_message_as_well(): void
    {
        $this->mock(Template::class, fn ($mock) =>
            $mock->shouldReceive('get')->once()->with('verification',8899)->andReturn('a template 8899')
        );
        $this->mock(Sms::class, fn($mock) =>
            $mock->shouldReceive('send')->once()->with('09081187899', 'a template 8899')
        );
        $this->signup([
            'phone' => '09081187899'
        ]);
    }

    /** @test */
    public function authenticated_user_cant_signup(): void
    {
        $this->signIn();
        $response = $this->signup();
        $response->assertForbidden();
    }

    /** @test */
    public function first_name_must_valid(): void
    {
        $name = 'first_name';
        $rule = ['required', 'string', 'max:50'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->signup($payload)
        );
    }

    /** @test */
    public function last_name_must_valid(): void
    {
        $name = 'last_name';
        $rule = ['required', 'string', 'max:50'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->signup($payload)
        );
    }

    /** @test */
    public function middle_name_must_valid(): void
    {
        $name = 'middle_name';
        $rule = ['nullable', 'string', 'max:50'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->signup($payload)
        );
    }

    /** @test */
    public function phone_must_valid(): void
    {
        $name = 'phone';
        $rule = ['required', 'string', 'max:11', 'unique:phone:'. User::class . ':09172149989'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->signup($payload)
        );

    }

    /** @test */
    public function password_must_valid(): void
    {
        $name = 'password';
        $rule = ['required', 'string', 'max:90', 'min:8'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->signup($payload)
        );
    }

    public function signup($overwrites = [])
    {
        return $this->postJson($this->endpoint,
            $this->userAttributes($overwrites)
        );
    }

    private function userAttributes(mixed $overwrites): array
    {
        $attributes = User::factory()->make()->toArray();
        $overwrites = $overwrites + ['password' => 'password'];
        return array_merge($attributes, $overwrites);
    }
}
