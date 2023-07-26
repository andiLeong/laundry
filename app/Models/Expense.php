<?php

namespace App\Models;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Expense extends Model
{
    use HasFactory;

    public function scopeGroupByCreated(Builder $query, $start, $end, $format = 'month')
    {
        $format = $format == 'month' ? '%Y-%m' : '%Y-%m-%d';
        return $query
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$format') as dt"),
                DB::raw('sum(amount) as total_amount')
            )
            ->where('created_at', '>', $start)
            ->where('created_at', '<', $end)
            ->groupBy('dt');
    }
}
