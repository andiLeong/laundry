<?php

namespace App\Models\Sms;
use App\Models\Sms\Contract\Sms as SmsContract;
use App\Models\SmsLog;

class Sms implements SmsContract
{

    public function send($number, $message): bool
    {




        SmsLog::create([
            'to' => $number,
            'message' => $message,
            'payload' => ['payload'],
            'result' => ['status' => 'success']
        ]);

        return true;
    }
}
