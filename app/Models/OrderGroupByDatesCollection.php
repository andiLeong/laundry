<?php

namespace App\Models;

use Carbon\Carbon;
use Carbon\CarbonPeriod;

class OrderGroupByDatesCollection
{
    protected Carbon $start;
    protected Carbon $end;
    protected string $format;
    private $collection;

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
        return collect($this->getDates())->map(fn($dt) => $this->apply($dt));
    }

    /**
     * return the default empty state
     * @param $dt
     * @return array
     */
    protected function emptyState($dt): array
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
     * apply data to the collection
     * @param Carbon $dt
     * @return array|mixed
     */
    protected function apply(Carbon $dt): mixed
    {
        $orders = $this->get();
        $dt = $dt->format($this->format);
        if ($orders->has($dt)) {
            return $orders[$dt]->toArray();
        }

        return $this->emptyState($dt);
    }

    /**
     * get data collection
     * @return mixed
     */
    protected function get()
    {
        if ($this->groupBy === 'day') {
            $arg = [$this->start, today()->copy()->addDay(), 'day'];
        } else {
            $arg = [$this->start, $this->end->copy()->addMonths()];
        }

        if(is_null($this->collection)){
            return $this->collection = Order::query()
                ->groupByCreated(...$arg)
                ->get()
                ->keyBy('dt');
        }

        return $this->collection;
    }

}
