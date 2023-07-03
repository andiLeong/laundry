<?php

namespace App\QueryFilter;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

trait Filterable
{
    public function scopeFilters(Builder $query, array $filterOptions, Request $request = null): Builder
    {
        $filterOptions ??= $this->getFilter();
        $filters = new QueryFilterManager($query, $filterOptions, $request);
        return $filters->apply();
    }

    public function getFilter()
    {
        throw new \LogicException('Please implements getFilter method');
    }

    public function scopeOrderFilters(Builder $query): Builder
    {
        $orderFilters = new OrderQueryFilter($query, $this->getOrderFilter());
        return $orderFilters->apply();
    }

    public function getOrderFilter()
    {
        throw new \LogicException('Please implements getOrderFilter method');
    }
}
