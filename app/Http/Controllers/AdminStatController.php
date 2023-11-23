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

        if ($request->get('days') !== 'all') {
            $query->passDays($request->get('days'));
        }

        $promotionCount = OrderPromotion::whereIn('order_id', $query->pluck('id'))->count();
        $orderCount = $query->count();
        $rate = $orderCount > 0
            ? (int)round(($promotionCount / $orderCount) * 100)
            : 0;

        return [
            'user_count' => User::count(),
            'order_count' => $orderCount,
            'total_order_amount' => $query->sum('total_amount'),
            'promotion_count' => $promotionCount,
            'order_promotion_rate' => $rate,
        ];
    }
}
