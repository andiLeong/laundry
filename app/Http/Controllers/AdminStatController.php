<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderPromotion;
use App\Models\User;
use Illuminate\Http\Request;

class AdminStatController extends Controller
{
    public function index()
    {
        return [
            'user_count' => User::count(),
            'order_count' => $orderCount = Order::count(),
            'total_order_amount' => Order::sum('amount'),
            'order_promotion_rate' => $orderCount > 0
                ? round(OrderPromotion::count() / $orderCount,2) * 100
                : 0,
        ];
    }
}
