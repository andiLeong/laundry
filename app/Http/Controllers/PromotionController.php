<?php

namespace App\Http\Controllers;

use App\Models\Promotion;
use Illuminate\Support\Facades\Cache;

class PromotionController extends Controller
{
    public function index()
    {
        return Cache::remember(
            'promotions',
            now()->addDays(5),
            fn() => Promotion::enabled()
                ->get(['id', 'name', 'description', 'start', 'until', 'isolated'])
        );
    }

    public function show(Promotion $promotion)
    {
        if(!$promotion->active()){
            abort(404,'Promotion not found');
        }

        return $promotion;
    }
}
