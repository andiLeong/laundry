<?php

namespace App\Models\Sms;
use App\Models\Sms\Contract\Sms as SmsContract;

class Sms implements SmsContract
{

    public function send($number, $message): bool
    {
        return true;
    }
}
