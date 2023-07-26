<?php

namespace App\Models;

use Carbon\Carbon;

class MarginGroupByMonthCollection extends OrderGroupByDatesCollection
{

    private $singleMargin;

    public function apply(Carbon $dt): mixed
    {
        $orders = $this->get();
        $expenses = $this->getExpense();
        $margins = $this->calculateMargin($orders,$expenses);
        $dt = $dt->format($this->format);
        if($margins->has($dt)){
            return $margins[$dt];
        }

        $this->singleMargin = 0;
        return $this->emptyState($dt);
    }

    public function emptyState($dt): array
    {
        return [
            'dt' => $dt,
            'margin' => $this->singleMargin,
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

    private function calculateMargin(mixed $orders, $expenses)
    {
        return $orders->map(function($order) use($expenses) {
            $this->singleMargin = $order['order_total_amount'] - $expenses[$order['dt']]->total_amount;
            return $this->emptyState($order['dt']);
        });
    }
}
