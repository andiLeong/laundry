<?php

namespace App\Http\Controllers;

use App\Models\Sms\Contract\Sms;
use App\Models\Sms\Template;
use App\Models\User;
use App\Models\VerificationToken;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class SignUpController extends Controller
{
    public function store(Request $request, Sms $sms, Template $template)
    {
        if (Auth::check()) {
            abort(403, 'You are already sign in, you cant perform this action');
        }

        $attributes = $request->validate([
            'phone' => 'required|string|max:11|unique:users,phone',
            'password' => 'required|string|max:90|min:8',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50'
        ]);

        return DB::transaction(function () use ($attributes, $sms, $template) {
            $code = VerificationToken::generate();
            $sms->send(
                $attributes['phone'],
                $template->get('verification', $code)
            );

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
