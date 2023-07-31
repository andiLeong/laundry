<?php

namespace App\Models;

use Carbon\Carbon;

class MarginGroupByMonthCollection extends OrderGroupByDatesCollection
{
    protected $expenses;

    public function apply(Carbon $dt): mixed
    {
        $dt = $dt->format($this->format);
        $orders = $this->get();
        $expenses = $this->getExpense();

        $monthlyExpense = isset($expenses[$dt]) ? $expenses[$dt]->total_amount : 0;
        $monthlyIncome = isset($orders[$dt]) ? $orders[$dt]->order_total_amount : 0;

        return [
            'dt' => $dt,
            'margin' => $monthlyIncome - $monthlyExpense,
        ];
    }

    private function getExpense()
    {
        if ($this->expenses) {
            return $this->expenses;
        }

        $arg = [$this->start, $this->end->copy()->addMonths()];

        return $this->expenses = Expense::query()
            ->groupByCreated(...$arg)
            ->get()
            ->keyBy('dt');
    }
}
