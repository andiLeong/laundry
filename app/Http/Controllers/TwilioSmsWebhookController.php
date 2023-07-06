<?php

namespace App\Http\Controllers;

use App\Models\Sms\Twilio;
use App\Models\SmsLog;
use Illuminate\Http\Request;

class TwilioSmsWebhookController extends Controller
{
    public function __invoke(Request $request)
    {
        $status = $request->get('MessageStatus');
        if (!$request->has('SmsSid')) {
            abort('400', 'Id not existed');
        }

        if (in_array($status, Twilio::HANDLED_WEBHOOK_STATUS)) {
            $log = SmsLog::where('vendor_id', $request->get('SmsSid'))->first();
            $log->update(['status' => $status]);
        }
        return 'success';
    }
}
