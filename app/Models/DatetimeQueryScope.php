<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

trait DatetimeQueryScope
{

    public function scopeToday(Builder $query)
    {
        return $query->where('created_at', '>=', today()->startOfDay())
            ->where('created_at', '<=', today()->endOfDay());
    }

    public function scopeCurrentMonth(Builder $query, $column = 'created_at')
    {
        return $query->where($column, '>=', today()->startOfMonth())
            ->where($column, '<=', today()->endOfMonth());
    }

    public function scopeCurrentWeek(Builder $query)
    {
        return $query
            ->where('created_at', '>=', now()->startOfWeek())
            ->where('created_at', '<=', now()->endOfWeek());
    }

    public function scopeCreateBetween(Builder $query, $start, $end)
    {
        return $query
            ->where('created_at', '>=', $start)
            ->where('created_at', '<=', $end);
    }

    public function scopePassDays(Builder $query, $days)
    {
        $start = today()->subDays($days - 1);
        $end = today()->copy()->addDay();
        return $query->createBetween($start, $end);
    }

    public function scopePassMonths(Builder $query, $months)
    {
        $start = today()->startOfMonth()->subMonths($months - 1);
        $end = today()->startOfMonth()->addMonths();
        return $query->createBetween($start, $end);
    }

    public function scopeGroupByCreated(Builder $query, $span, $format = 'month')
    {
        if ($format == 'month') {
            $format = '%Y-%m';
            $method = 'passMonths';
        } else {
            $format = '%Y-%m-%d';
            $method = 'passDays';
        }

        return $query
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$format') as dt"),
                DB::raw('count(id) as order_count'),
                DB::raw('sum(total_amount) as order_total_amount')
            )
            ->{$method}($span)
            ->groupBy('dt');
    }

}
