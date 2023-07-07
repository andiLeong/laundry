<?php

namespace App\Models\Sms;

use App\Models\Sms\Contract\Sms as SmsContract;
use App\Models\Sms\Exception\SendSmsFailureException;
use App\Models\SmsLog;
use Exception;
use Twilio\Rest\Client as TwilioSdk;

class Twilio implements SmsContract
{
    protected Exception $e;
    private string $to;
    private string $message;

    const HANDLED_WEBHOOK_STATUS = [
        'sent', 'delivered', 'failed'
    ];

    public function __construct(
        private readonly TwilioSdk $twilio,
        private readonly string    $number,
    )
    {
        //
    }

    public function send($number, $message): bool
    {
        $this->to = $number;
        $this->message = $message;

        dd('real sms implementation');
        try {
            $result = $this->twilio->messages->create(
                '+63' . $number,
                ['from' => $this->number, 'body' => $message]
            );
        } catch (Exception $e) {
            $this->e = $e;
            $this->logAndExcept(['message' => $this->e->getMessage()]);
        }

        $result = $result->toArray();
        if ($this->success($result['errorCode'], $result['errorMessage'])) {
            $this->log($result, [
                'vendor_id' => $result['sid'],
                'status' => $result['status'],
            ]);
            return true;
        }

        $this->e = new SendSmsFailureException($result['errorMessage']);
        $this->logAndExcept($result);
        throw $this->e;
    }

    protected function success($errorCode, $errorMessage): bool
    {
        return is_null($errorCode) && is_null($errorMessage);
    }

    protected function log($result = [], $additional = []): void
    {
        SmsLog::create(array_merge([
            'to' => $this->to,
            'message' => $this->message,
            'payload' => ['from' => $this->number, 'body' => $this->message],
            'result' => $result,
        ], $additional));
    }

    protected function logAndExcept($result = [])
    {
        $this->log($result);
        throw new SendSmsFailureException($this->e->getMessage());
    }
}
