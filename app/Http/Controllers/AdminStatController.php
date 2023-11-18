<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\User;
use Illuminate\Http\Request;

class AdminStatController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if($request->get('days') !== 'all'){
            $query->passDays($request->get('days'));
        }

        return [
            'user_count' => User::count(),
            'order_count' => $orderCount = $query->count(),
            'total_order_amount' => $query->sum('total_amount'),
            'order_promotion_rate' => $orderCount > 0
                ? round(OrderPromotion::whereIn('order_id',$query->pluck('id'))->count() / $orderCount,2) * 100
                : 0,
        ];
    }
}
