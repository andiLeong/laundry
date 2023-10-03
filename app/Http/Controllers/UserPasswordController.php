<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class UserPasswordController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        $attributes = $request->validate([
            'password' => 'required|string|max:90|min:8|confirmed',
        ]);
        $user->update($attributes);
        return $user;
    }
}
