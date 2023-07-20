<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderGroupByDatesCollection;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class AdminOrderStatController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($request->filled('group_by_months')) {
            $months = $request->get('group_by_months');
            $collection = new OrderGroupByDatesCollection($months,'month');
            return $collection();
        }

        if ($request->filled('group_by_days')) {
            $days = $request->get('group_by_days');
            $collection = new OrderGroupByDatesCollection($days);
            return $collection();
        }


        if ($request->filled('timeframe') && $request->get('timeframe') === 'monthly') {
            $query->currentMonth();
        } else if ($request->filled('timeframe') && $request->get('timeframe') === 'weekly') {
            $query->currentWeek();
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
