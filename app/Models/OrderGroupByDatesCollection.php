<?php

namespace App\Models;

use Carbon\CarbonPeriod;
use Illuminate\Support\Carbon;

class OrderGroupByDatesCollection
{
    private Carbon $start;
    private Carbon $end;
    private string $format;

    public function __construct(protected int $span, protected $groupBy = 'day')
    {
        if ($this->groupBy === 'day') {
            $this->start = today()->subDays($span - 1);
            $this->end = today();
            $this->format = 'Y-m-d';
        } else {
            $this->start = today()->startOfMonth()->subMonths($span - 1);
            $this->end = today()->startOfMonth();
            $this->format = 'Y-m';
        }
    }

    public function __invoke()
    {
        return collect($this->getDates())->map(fn($dt) => $this->addOrder($dt));
    }

    /**
     * return the default empty state
     * @param $dt
     * @return array
     */
    public function emptyState($dt): array
    {
        return [
            'dt' => $dt,
            'order_count' => 0,
            'order_total_amount' => 0,
        ];
    }

    /**
     * generate dates
     * @return \Carbon\CarbonInterface[]
     */
    protected function getDates(): array
    {
        if ($this->groupBy === 'day') {
            return CarbonPeriod::create($this->start, $this->end)->toArray();
        }
        return CarbonPeriod::create($this->start, '1 month', $this->end)->toArray();
    }

    /**
     * add order data to date collection
     * @param $dt
     * @return array|mixed
     */
    protected function addOrder($dt): mixed
    {
        $orders = $this->queryOrder();
        $dt = $dt->format($this->format);
        if ($orders->has($dt)) {
            return $orders[$dt]->toArray();
        }

        return $this->emptyState($dt);
    }

    /**
     * get order collection
     * @return mixed
     */
    protected function queryOrder()
    {
        if ($this->groupBy === 'day') {
            $arg = [$this->start, today()->copy()->addDay(), 'day'];
        } else {
            $arg = [$this->start, $this->end->copy()->addMonths()];
        }

        return Order::query()
            ->groupByCreated(...$arg)
            ->get()
            ->keyBy('dt');
    }

}
