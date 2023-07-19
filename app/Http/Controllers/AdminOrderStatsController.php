<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Carbon\CarbonPeriod;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AdminOrderStatsController extends Controller
{
    public function index(Request $request)
    {
        $query = Order::query();

        if ($request->filled('group_by_days')) {
            $days = $request->get('group_by_days');
            $end = now()->addDay()->startOfDay();
            $start = now()->addDay()->startOfDay()->subDays($days);
            $dates = CarbonPeriod::create($start, $end)->toArray();
            $orders = Order::query()
                ->select(
                    DB::raw('date(created_at) as dt'),
                    DB::raw('count(id) as order_count'),
                    DB::raw('sum(amount) as order_total_amount')
                )
                ->where('created_at', '<=', now())
                ->where('created_at', '>=', now()->subDays($days))
                ->groupBy('dt')
                ->get()
                ->keyBy('dt');

            return collect($dates)->map(function ($dt) use ($orders) {
                $dt = $dt->format('Y-m-d');
                if ($orders->has($dt)) {
                    return $orders[$dt]->toArray();
                }

                return [
                    'dt' => $dt,
                    'order_count' => 0,
                    'order_total_amount' => 0,
                ];
            });
        }


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
