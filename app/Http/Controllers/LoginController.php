<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class LoginController extends Controller
{

    public function store(Request $request)
    {
        $user = User::where('phone', $request->phone)->first();

        if (is_null($user)){
            throw ValidationException::withMessages([
                'phone' => 'Login Failed, please check your number'
            ]);
        }

        if (!$user->isVerified()) {
            throw ValidationException::withMessages([
                'phone' => 'Please verify your number before login'
            ]);
        }

        if (!password_verify($request->password, $user->password)) {
            throw ValidationException::withMessages([
                'phone' => 'Login Failed, please check your credential'
            ]);
        }

        Auth::login($user);
        $request->session()->regenerate();
    }
}
