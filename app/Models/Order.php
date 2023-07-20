<?php

namespace App\Models;

use App\QueryFilter\Filterable;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Order extends Model
{
    use HasFactory;
    use Filterable;

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function promotions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'order_promotions', 'order_id', 'promotion_id');
    }

    public function scopeToday(Builder $query)
    {
        return $query->where('created_at', '>', today()->startOfDay())
            ->where('created_at', '<', today()->endOfDay());
    }

    public function scopeCurrentMonth(Builder $query)
    {
        return $query->where('created_at', '>', today()->startOfMonth())
            ->where('created_at', '<', today()->endOfMonth());
    }

    public function scopeCurrentWeek(Builder $query)
    {
        return $query
            ->where('created_at', '>=', now()->startOfWeek())
            ->where('created_at', '<=', now()->endOfWeek());
    }

    public function scopeGroupByCreated(Builder $query, $start, $end, $format = 'month')
    {
        $format = $format == 'month' ? '%Y-%m' : '%Y-%m-%d';
        return $query
            ->select(
                DB::raw("DATE_FORMAT(created_at, '$format') as dt"),
                DB::raw('count(id) as order_count'),
                DB::raw('sum(amount) as order_total_amount')
            )
            ->where('created_at', '>', $start)
            ->where('created_at', '<', $end)
            ->groupBy('dt');
    }
}
