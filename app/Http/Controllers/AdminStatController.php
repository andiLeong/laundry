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
            'foo' => $orderCount > 0 ? round($promotionCount / $orderCount, 2) : 'no',
            'foo1' => $orderCount > 0 ? $promotionCount / $orderCount : 'no',
            'foo2' => $orderCount > 0 ? round($promotionCount / $orderCount, 2) : 'no',
            'foo3' => $orderCount > 0 ? round($promotionCount / $orderCount, 2) * 100 : 'no',
            'foo4' => 0.29 * 100,
        ];
    }
}
