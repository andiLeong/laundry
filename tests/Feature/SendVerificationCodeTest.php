<?php

namespace Tests\Feature;

use App\Models\SmsLog;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use Tests\UserCanBeVerified;

class SendVerificationCodeTest extends TestCase
{
    use LazilyRefreshDatabase;
    use UserCanBeVerified;

    protected string $endpoint = 'api/verification-code/send';
    private string $phone;
    private int $code;

    protected function setUp(): void
    {
        parent::setUp();
        $this->phone = '09081187899';
        $this->code = 8899;
    }

    /** @test */
    public function it_can_send_a_verification_code_to_user_phone_with_correct_number_and_message(): void
    {
        $this->setUserForVerification();
        $this->fakeSms()
            ->postJson($this->endpoint . '/' . $this->user->phone)
            ->assertOk();
    }

    /** @test */
    public function once_sms_code_is_sent_an_sms_log_is_recorded()
    {
        $this->assertDatabaseCount('sms_logs', 0);
        $this->setUserForVerification()
            ->fakeSms()
            ->postJson($this->endpoint . '/' . $this->user->phone);

        $log = SmsLog::first();
        $this->assertNotNull($log);
        $this->assertEquals($this->user->phone, $log->to);
        $this->assertEquals(8899, $log->message);
        $this->assertDatabaseCount('sms_logs', 1);
    }

    /** @test */
    public function once_sms_code_is_sent_verification_token_record_is_added()
    {
        $this->setUserForVerification();
        $user = $this->user;
        $this->assertNull($user->verification);
        $this->fakeSms()->postJson($this->endpoint . '/' . $user->phone);

        $this->assertEquals($this->code, $user->fresh()->verification->token);
    }

    /** @test */
    public function if_send_sms_fails_it_should_return_proper_response()
    {
        $this->markTestSkipped();
    }

    /** @test */
    public function if_user_is_verified_there_is_no_reason_to_send()
    {
        $user = User::factory()->create();
        $this->postJson($this->endpoint . '/' . $user->phone)->assertForbidden();
    }

    /** @test */
    public function if_user_not_existed_it_should_gets_404()
    {
        $this->postJson($this->endpoint . '/09787451566')->assertNotFound();
    }

    /** @test */
    public function its_only_available_for_non_sign_in_user()
    {
        $this->setUserForVerification();
        $this->signIn()
            ->postJson($this->endpoint . '/' . $this->user->phone)
            ->assertForbidden();
    }

    /** @test */
    public function it_abort_the_request_if_user_is_abusing_the_endpoint()
    {
        $this->markTestSkipped();
    }
}
