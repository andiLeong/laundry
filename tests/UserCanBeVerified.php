<?php

namespace Tests;

use App\Models\Sms\Contract\Sms;
use App\Models\Sms\Template;
use App\Models\SmsLog;
use App\Models\User;

trait UserCanBeVerified
{

    public function fakeSms($code = 8899, $phone = '09081187899')
    {
        $this->mock(Template::class,
            fn($mock) => $mock->shouldReceive('get')->once()->with('verification', $code)->andReturn($code)
        );
        $this->mock(Sms::class,
            fn($mock) => $mock->shouldReceive('send')->once()->with($phone, $code)
        );

        $this->createSmsLog($phone,$code);
        return $this;
    }

    public function createSmsLog($number, $message)
    {
        SmsLog::create([
            'to' => $number,
            'message' => $message,
            'payload' => ['payload'],
            'result' => ['status' => 'success']
        ]);
    }

    public function setVerifiedUser($phone = null, $verifiedAt = null)
    {
        $phone ??= $this->phone;
        $this->user = User::factory()->create([
            'phone' => $phone,
            'phone_verified_at' => $verifiedAt
        ]);

        return $this;
    }
}
