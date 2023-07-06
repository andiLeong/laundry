<?php

namespace Tests\Feature;

use App\Models\Sms\Twilio;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TwilioSmsWebhookTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected string $endpoint = '/api/twilio/sms';

    /** @test */
    public function it_request_does_not_have_id_it_gets_400(): void
    {
        $response = $this->post($this->endpoint);
        $response->assertStatus(400);
    }

    /** @test */
    public function it_can_update_sms_log_status(): void
    {
        $log = SmsLog::create([
            'vendor_id' => '123456',
            'to' => '09042145487',
            'status' => 'queued',
            'message' => 'hi world',
            'payload' => ['payload'],
            'result' => ['result'],
        ]);

        $this->post($this->endpoint, [
            'MessageStatus' => 'delivered',
            'SmsSid' => '123456'
        ]);

        $this->assertEquals('delivered', $log->fresh()->status);
    }

    /** @test */
    public function it_only_update_the_status_that_defined_inside_our_end(): void
    {
        $log = SmsLog::create([
            'vendor_id' => '123456',
            'to' => '09042145487',
            'status' => 'queued',
            'message' => 'hi world',
            'payload' => ['payload'],
            'result' => ['result'],
        ]);

        $newStatus = 'sending';
        $this->assertTrue(!in_array($newStatus,Twilio::HANDLED_WEBHOOK_STATUS));

        $this->post($this->endpoint, [
            'MessageStatus' => $newStatus,
            'SmsSid' => '123456'
        ]);

        $this->assertEquals('queued', $log->fresh()->status);
    }
}
