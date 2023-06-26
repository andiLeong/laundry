<?php

namespace App\Http\Controllers;

use App\Models\Promotion;

class AvailablePromotionController extends Controller
{
    public function index()
    {
        return Promotion::where('status', 1)->where(function($query){
            return $query->whereNull('until')->orWhere('until', '>', now());
        })->get(['id','name','description','start','until','isolated']);
    }
}
