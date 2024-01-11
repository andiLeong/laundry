<?php

namespace App\Http\Controllers;

use App\Models\GoogleRecaptcha;
use App\Models\Sms\Contract\Sms;
use App\Models\Sms\Template;
use App\Models\User;
use App\Models\VerificationToken;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SignUpController extends Controller
{
    public function store(Request $request, Sms $sms, Template $template, GoogleRecaptcha $googleRecaptcha)
    {
        $attributes = $request->validate([
            'phone' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail) {
                if (is_string($value) && strlen($value) != 11) {
                    $fail("The {$attribute} is invalid.");
                }

                if (is_string($value) && !str_starts_with($value, '09')) {
                    $fail("The {$attribute} is invalid.");
                }

                $user = User::withoutGlobalScope('verified')->where('phone', $value)->first();
                if ($user !== null) {
                    if ($user->isVerified()) {
                        $fail("your account is already verified, you can sign in with your number");
                    } else {
                        $fail("we found your record, but you are not verified, please go to verify.");
                    }
                }
            }],
            'password' => 'required|string|max:90|min:8',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50',
            'recaptcha_token' => ['required', 'string'],
        ]);

        if (!$googleRecaptcha->pass($attributes['recaptcha_token'])) {
            throw ValidationException::withMessages(['recaptcha_token' => 'Opps, something went wrong.']);
        }

        try {
            $code = VerificationToken::generate();
            $sms->send(
                $attributes['phone'],
                $template->get('verification', $code)
            );
        } catch (\Exception $e) {
            abort(502, $e->getMessage());
        }

        unset($attributes['recaptcha_token']);
        return DB::transaction(function () use ($attributes, $code) {
            $user = User::create($attributes);
            VerificationToken::create([
                'token' => $code,
                'user_id' => $user->id,
                'expired_at' => now()->addMinutes(5)
            ]);
            return $user;
        });
    }
}
