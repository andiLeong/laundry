<?php

namespace App\Http\Controllers;

use App\Http\Validation\AddressValidation;
use App\Models\Address;
use Closure;
use Exception;
use Illuminate\Http\Request;

class AddressController extends Controller
{
    public function index()
    {
        $address = Address::select('addresses.room', 'places.name', 'places.address','addresses.id')
            ->leftJoin('places', 'places.id', '=', 'addresses.place_id')
            ->where('addresses.user_id', auth()->id())
            ->orderBy('addresses.created_at', 'desc')
            ->get();
        return ['data' => $address];
    }

    public function store(Request $request, AddressValidation $addressValidation)
    {
        $attribute = $request->validate([
            'room' => 'nullable|string|max:100',
            'place_id' => ['required', 'string',
                function (string $attribute, mixed $value, Closure $fail) use ($addressValidation) {
                    try {
                        $addressValidation->validate($value);
                    } catch (Exception $e) {
                        $fail($e->getMessage());
                    }
                }
            ]
        ]);
        $attribute['place_id'] = $addressValidation->getPlace()->id;
        $attribute['user_id'] = auth()->id();

//        dd($attribute);
        return Address::create($attribute);
    }

    public function update(Address $address, Request $request)
    {
        if ($address->user_id !== auth()->id()) {
            abort(403, 'You do not have the right to perform this action');
        }
        $address->update($request->validate([
            'room' => 'required|string|max:100',
        ]));
    }

    public function destroy(Address $address)
    {
        if ($address->user_id !== auth()->id()) {
            abort(403, 'You do not have the right to perform this action');
        }
        $address->delete();
    }
}
