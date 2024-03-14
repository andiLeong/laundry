<?php

namespace App\Models;

class DeliveryFeeCalculator
{
    /**
     * @var \Illuminate\Config\Repository|\Illuminate\Contracts\Foundation\Application|\Illuminate\Foundation\Application|mixed
     */
    private mixed $config;

    /**
     * the actual distance in meter between 2 point
     * @var int
     */
    private int $distance;

    private Place $to;
    private Branch $from;

    public function __construct(Place $to, Branch $from)
    {
        $this->config = config('delivery_fee');
        $this->to = $to;
        $this->from = $from;
    }

    public function calculate()
    {
        if ($this->free()) {
            return 0;
        }

        $actualDistance = $this->distance()->calculate();
        $this->distance = $actualDistance;

        if ($actualDistance < $this->config['min_meter']) {
            return 0;
        }

        foreach ($this->config['price'] as $distance => $fee) {
            if ($actualDistance >= $distance) {
                return $fee;
            }
        }
    }

    /**
     * check a place is within free list
     */
    private function free(): bool
    {
        return in_array($this->to->id, $this->config['free']);
    }

    public function getDistance(): int
    {
        return $this->distance;
    }

    /**
     * get a distance calculator instance
     */
    protected function distance(): DistanceCalculator
    {
        $to = new Coordinate($this->to->longitude, $this->to->latitude);
        $from = new Coordinate($this->from->longitude, $this->from->latitude);
        return new DistanceCalculator($from, $to);
    }
}
