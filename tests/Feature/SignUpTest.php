<?php

namespace Tests\Feature;

use App\Models\Sms\Contract\Sms;
use App\Models\Sms\Exception\SendSmsFailureException;
use App\Models\Sms\Template;
use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;
use Tests\UserCanBeVerified;
use Tests\Validate;

class SignUpTest extends TestCase
{
    use LazilyRefreshDatabase;
    use UserCanBeVerified;

    protected string $endpoint = '/api/signup';

    protected function setUp(): void
    {
        parent::setUp();
        $this->phone = '09081187899';
        $this->code = 8899;
    }

    /** @test */
    public function it_can_sign_up_user(): void
    {
        $response = $this->fakeSms()->signUpWithPhone()->assertSuccessful();
        $user = User::findUnverifiedById($response->json('id'));
        $this->assertNotNull($user);
    }

    /** @test */
    public function after_signup_phone_still_not_been_verified(): void
    {
        $response = $this->fakeSms()->signUpWithPhone();
        $user = User::findUnverifiedById($response->json('id'));
        $this->assertFalse($user->isVerified());
    }

    /** @test */
    public function after_signup_user_type_is_customer(): void
    {
        $response = $this->fakeSms()->signUpWithPhone();
        $user = User::findUnverifiedById($response->json('id'));
        $this->assertTrue($user->isCustomer());
    }

    /** @test */
    public function after_signup_an_user_object_is_return_but_exclude_sensitive_information(): void
    {
        $this->fakeSms();
        $response = $this->signup([
            'password' => 'new pass',
            'phone' => $this->phone
        ]);
        $this->assertNull($response->json('password'));
    }

    /** @test */
    public function after_signup_user_password_is_hash(): void
    {
        $this->fakeSms();
        $password = '1234qwer';
        $response = $this->signup([
            'password' => $password,
            'phone' => $this->phone
        ]);

        $user = User::findUnverifiedById($response->json('id'));
        $this->assertNotEquals($user->password, $password);
        $this->assertTrue(password_verify($password, $user->password));
    }

    /** @test */
    public function after_signup_a_verification_is_recorded(): void
    {
        $this->assertDatabaseCount('verification_tokens', 0);
        $response = $this->fakeSms()->signUpWithPhone();
        $user = User::findUnverifiedById($response->json('id'));
        $verification = $user->verification;
        $mins = $verification->expired_at->diffInMinutes(now());

        $this->assertTrue($mins <= 5);
        $this->assertFalse($mins > 5);
        $this->assertNotNull($user->verification->token);
    }

    /** @test */
    public function verification_sms_must_send_to_the_correct_number_and_message_as_well(): void
    {
        $this->fakeSms()->signUpWithPhone();
    }

    /** @test */
    public function once_sms_code_is_sent_an_sms_log_is_recorded()
    {
        $this->assertDatabaseCount('sms_logs', 0);
        $response = $this->fakeSms()->signUpWithPhone();
        $user = User::findUnverifiedById($response->json('id'));

        $log = SmsLog::first();
        $this->assertNotNull($log);
        $this->assertEquals($user->phone, $log->to);
        $this->assertEquals(8899, $log->message);
        $this->assertDatabaseCount('sms_logs', 1);
    }

    /** @test */
    public function if_sms_got_exception_we_should_throw_a_proper_response()
    {
        $code = 8899;
        $this->mock(Template::class,
            fn($mock) => $mock->shouldReceive('get')->once()->with('verification', $code)->andReturn($code)
        );
        $this->mock(Sms::class,
            fn(MockInterface $mock) => $mock
                ->shouldReceive('send')
                ->andThrow(SendSmsFailureException::class, 'Service unavailable')
        );

        $this->signUpWithPhone()->assertStatus(502);
    }

    /** @test */
    public function authenticated_user_cant_signup(): void
    {
        $this->signIn();
        $this->signup()->assertForbidden();
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
        $rule = ['required', 'unique:phone:' . User::class . ':09172149989'];
        Validate::name($name)->against($rule)->through(
            fn($payload) => $this->signup($payload)
        );

        //sign up with not phone number less than 11
        $response = $this->signUpWithPhone('0906014783');
        $this->assertValidateMessage('The phone is invalid.', $response, 'phone');

        //sign up with not phone number not starts with 09
        $response = $this->signUpWithPhone('01060147839');
        $this->assertValidateMessage('The phone is invalid.', $response, 'phone');

        //sign up with phone that is verified
        $response = $this->setVerifiedUser('09060147788')->signUpWithPhone($this->user->phone);
        $this->assertValidateMessage('your account is already verified, you can sign in with your number', $response, 'phone');

        //sign up with phone that is unverified
        $response = $this->setUserForVerification('09060778966')->signUpWithPhone($this->user->phone);
        $this->assertValidateMessage('we found your record, but you are not verified, please go to verify.', $response, 'phone');
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

    /** @test */
    public function it_can_detect_spam_if_field_is_filled(): void
    {
        $config = config('honeypot');

        $response = $this->signup(['notification' => 'bar']);
        $body = $response->json();
        $code = $response->getStatusCode();

        $this->assertEquals($body['message'], $config['message']);
        $this->assertEquals($code, $config['code']);
    }

    public function signUpWithPhone($phone = null)
    {
        $phone ??= $this->phone;
        return $this->signup(['phone' => $phone]);
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
