<?php

namespace Tests\Feature;

use App\Models\Sms\Contract\Sms;
use App\Models\SmsLog;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TwilioTest extends TestCase
{
    use LazilyRefreshDatabase;

    /** @test */
    public function it_can_send_sms(): void
    {
        $this->markTestSkipped();
        $sms = $this->app->get(Sms::class);
        $sms->send('+639060181233', 'hello send from phpunit');
//        dd(SmsLog::all()->toArray());
    }
}
