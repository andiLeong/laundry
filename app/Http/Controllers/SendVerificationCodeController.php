<?php

namespace App\Http\Controllers;

use App\Models\Sms\Contract\Sms;
use App\Models\Sms\Template;
use App\Models\User;
use App\Models\VerificationToken;

class SendVerificationCodeController extends Controller
{
    public function store($phone, Sms $sms, Template $template)
    {
        $user = User::withoutGlobalScope('verified')->where('phone', $phone)->first();
        if (is_null($user)) {
            abort(404, 'Phone not found');
        }

        if ($user->isVerified()) {
            abort(403, 'Your phone is verified');
        }

        try {
            $code = VerificationToken::generate();
            $sms->send(
                $user->phone,
                $template->get('verification', $code)
            );
        } catch (\Exception $e) {
            abort(502, $e->getMessage());
        }

        VerificationToken::create([
            'token' => $code,
            'user_id' => $user->id,
            'expired_at' => now()->addMinutes(5)
        ]);
    }
}
