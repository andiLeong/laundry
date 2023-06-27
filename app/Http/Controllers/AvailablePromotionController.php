<?php

namespace App\Http\Controllers;

use App\Models\Promotion;

class AvailablePromotionController extends Controller
{
    public function index()
    {
        return Promotion::enabled()
            ->available()
            ->get(['id','name','description','start','until','isolated']);
    }
}
