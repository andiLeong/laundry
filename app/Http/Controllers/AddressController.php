<?php

namespace App\Http\Controllers;

use App\Models\Address;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        return Address::where('user_id',auth()->id())->get();
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $attributes = $this->validateAttribute($request);

        return Address::create(array_merge($attributes, ['user_id' => $user->id]));
    }

    public function update(Address $address,Request $request)
    {
        $address->update($this->validateAttribute($request));
    }

    public function destroy(Address $address)
    {
        if($address->user_id !== auth()->id()){
            abort(403, 'You do not have the right to perform this action');
        }
        $address->delete();
    }

    protected function validateAttribute(Request $request)
    {
        return $request->validate([
            'city' => 'required|string|max:100',
            'number' => 'required|string|max:100',
            'province' => 'required|string|max:100',
            'street' => 'required|string|max:255',
            'name' => 'nullable|string|max:100',
        ]);
    }
}
