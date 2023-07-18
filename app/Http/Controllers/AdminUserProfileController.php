<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class AdminUserProfileController extends Controller
{
    public function __invoke(Request $request)
    {
        $user = auth()->user();
        $attributes = $request->validate([
            'first_name' => 'required|string|max:50',
            'last_name' => 'required|string|max:50',
            'middle_name' => 'nullable|string|max:50',
        ]);
        $user->update($attributes);
        return $user;
    }
}
