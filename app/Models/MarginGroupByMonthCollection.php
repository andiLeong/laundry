<?php

namespace App\Models;

use Carbon\Carbon;

class MarginGroupByMonthCollection extends OrderGroupByDatesCollection
{

    public function apply(Carbon $dt): mixed
    {
        $dt = $dt->format($this->format);
        $orders = $this->get();
        $expenses = $this->getExpense();

        if (isset($expenses[$dt])) {
            $monthlyExpense = $expenses[$dt]->total_amount;
        } else {
            $monthlyExpense = 0;
        }

        if (isset($orders[$dt])) {
            $monthlyIncome = $orders[$dt]->order_total_amount;
        } else {
            $monthlyIncome = 0;
        }

        return [
            'dt' => $dt,
            'margin' => $monthlyIncome - $monthlyExpense,
        ];
    }

    private function getExpense()
    {
        $arg = [$this->start, $this->end->copy()->addMonths()];

        return Expense::query()
            ->groupByCreated(...$arg)
            ->get()
            ->keyBy('dt');
    }
}
