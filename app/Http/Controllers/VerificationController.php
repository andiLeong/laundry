<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class VerificationController extends Controller
{
    public function store(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();
        if (is_null($user)) {
            throw ValidationException::withMessages([
                'phone' => ['Phone is invalid'],
            ]);
        }

        if ($user->isVerified()) {
            abort(404, 'Page Not Found');
        }

        $verification = $user->verification;
        if (is_null($verification) || $request->token != $verification->token || $verification->expired_at < now()) {
            throw ValidationException::withMessages([
                'token' => ['Token is invalid'],
            ]);
        }

        $user->phone_verified_at = now();
        $user->save();
    }
}
