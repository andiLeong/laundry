<?php

namespace App\Http\Controllers;

use App\Models\Sms\Contract\Sms;
use App\Models\Sms\Template;
use App\Models\User;
use App\Models\VerificationToken;
use Closure;
use Illuminate\Http\Request;

class SignUpController extends Controller
{
    public function store(Request $request, Sms $sms, Template $template)
    {
        $attributes = $request->validate([
            'phone' => ['required', 'string', 'unique:users,phone', function (string $attribute, mixed $value, Closure $fail) {
                if (is_string($value) && strlen($value) != 11) {
                    $fail("The {$attribute} is invalid.");
                }

                if (is_string($value) && !str_starts_with($value, '09')) {
                    $fail("The {$attribute} is invalid.");
                }
            }],
            'password' => 'required|string|max:90|min:8',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50'
        ]);

        try {
            $code = VerificationToken::generate();
            $sms->send(
                $attributes['phone'],
                $template->get('verification', $code)
            );
        } catch (\Exception $e) {
            abort(502, $e->getMessage());
        }

        $user = User::create($attributes);
        VerificationToken::create([
            'token' => $code,
            'user_id' => $user->id,
            'expired_at' => now()->addMinutes(5)
        ]);
        return $user;

    }
}
