<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\DeliveryFeeCalculator;
use App\Models\Place;

class DeliveryFeeController extends Controller
{
    public function get($id)
    {
        $place = Place::where('id',$id)->first();
        if(is_null($place)){
            abort(404,'Place not found');
        }
        return ['amount' => (new DeliveryFeeCalculator($place, Branch::first()))->calculate()];
    }
}
