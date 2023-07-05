<?php

namespace App\Models\Sms;

use App\Models\Sms\Contract\Sms as SmsContract;
use App\Models\SmsLog;
use Twilio\Rest\Client as TwilioSdk;

class Twilio implements SmsContract
{

    public function __construct(
        private readonly TwilioSdk $twilio,
        private readonly string $number,
    )
    {
        //
    }

    public function send($number, $message): bool
    {
        dd('sms real implementation');
        $result = $this->twilio->messages->create(
            $number,
            ['from' => $this->number, 'body' => $message]
        );

        dump($result->toArray());

        SmsLog::create([
            'to' => $number,
            'message' => $message,
            'payload' => ['from' => $this->number, 'body' => $message],
            'result' => $result->toArray(),
        ]);

        return true;
    }
}
