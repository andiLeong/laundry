<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class AdminOrderStatsController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($request->filled('timeframe') && $request->get('timeframe') === 'monthly') {
            $query->monthly();
        } else if ($request->filled('timeframe') && $request->get('timeframe') === 'weekly') {
            $query->weekly();
        } else {
            $query->today();
        }

        $orders = $query->get();

        return [
            'order_count' => count($orders),
            'order_total_amount' => $orders->sum('amount'),
        ];
    }
}
