<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SignUpController extends Controller
{
    public function store(Request $request)
    {
        if(Auth::check()){
            abort(403, 'You are already sign in, you cant perform this action');
        }

        $attributes = $request->validate([
            'phone' => 'required|string|max:11|unique:users,phone',
            'password' => 'required|string|max:90|min:8',
            'first_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
            'last_name' => 'required|string|max:50'
        ]);

        return User::create($attributes);
    }
}
