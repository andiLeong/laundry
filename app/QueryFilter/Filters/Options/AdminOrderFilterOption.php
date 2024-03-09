<?php

namespace App\QueryFilter\Filters\Options;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class AdminOrderFilterOption extends Option
{
    protected array $keys = [
        'user_id',
        'description',
        'paid',
        'payment',
        'date',
        'include_user',
        'phone',
        'first_name',
        'filter_by_days',
        'id',
    ];

    protected function id(): array
    {
        return [
            'clause' => 'whereIn',
            'value' => explode(',', $this->request->get('id')),
        ];
    }

    protected function date(): array
    {
        return [
            'clause' => 'whereBetween',
            'column' => 'created_at',
            'value' => [$this->request->get('start'), $this->request->get('end')],
            'should_attach_query' => fn($request) => $request->filled('start') && $request->filled('end'),
        ];
    }

    protected function includeUser(): array
    {
        return [
            'clause' => 'whereNotNull',
            'column' => 'user_id',
            'should_attach_query' => fn($request) => $request->get('include_user') == 'true' || $request->get('include_user') == '1',
        ];
    }

    protected function phone(): array
    {
        return [
            'clause' => 'whereHas',
            'relationship' => 'user',
        ];
    }

    protected function firstName(): array
    {
        return [
            'clause' => 'whereHas',
            'relationship' => 'user',
        ];
    }

    protected function filterByDays(): array
    {
        return [
            'clause' => 'callBack',
            'callback' => function (Builder $query, Request $request) {
                $day = $request->get('filter_by_days');
                return match (true) {
                    $day === 'today' => $query->today(),
                    $day === 'week' => $query->currentWeek(),
                    is_int((int)$day) => $query->passdays($day),
                    default => $query,
                };
            }
        ];
    }
}
