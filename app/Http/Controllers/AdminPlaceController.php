<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DeliveryFeeCalculator;
use App\Models\Place;
use Illuminate\Http\Request;

class AdminPlaceController extends Controller
{
    public function index(Request $request)
    {
        return Place::query()
            ->filters(['name' => []], $request)
            ->orderBy('id', 'desc')
            ->paginate()
            ->through(function ($place){
                $calculator = new DeliveryFeeCalculator($place, Branch::first());
                $place->delivery_fee = $calculator->calculate();
                return $place;
            });
    }
}
