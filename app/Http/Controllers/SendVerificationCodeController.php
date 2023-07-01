<?php

namespace App\Http\Controllers;

use App\Models\Sms\Contract\Sms;
use App\Models\Sms\Template;
use App\Models\SmsLog;
use App\Models\User;
use App\Models\VerificationToken;

class SendVerificationCodeController extends Controller
{
    public function store(User $user, Sms $sms, Template $template)
    {
        if ($user->isVerified()) {
            abort(403, 'Your phone is verified');
        }

        $code = VerificationToken::generate();
        $sms->send(
            $user->phone,
            $template->get('verification', $code)
        );

        VerificationToken::create([
            'token' => $code,
            'user_id' => $user->id,
            'expired_at' => now()->addMinutes(5)
        ]);
    }
}
