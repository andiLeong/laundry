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

        $actualDistance = $this->computeDistance();
        $this->distance = $actualDistance;
//        dump($actualDistance);

        if($actualDistance < $this->config['min_meter']){
            return 0;
        }

        foreach ($this->config['price'] as $distance => $fee){
            if($actualDistance >= $distance){
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

    /**
     * get the point to point distance between 2 places
     */
    protected function computeDistance()
    {
        $to = new Coordinate($this->to->longitude,$this->to->latitude);
        $from = new Coordinate($this->from->longitude,$this->from->latitude);

        $p1 = deg2rad($to->latitude);
        $p2 = deg2rad($from->latitude);
        $dp = deg2rad($from->latitude - $to->latitude);
        $dl = deg2rad($from->longitude - $to->longitude);
        $a = (sin($dp/2) * sin($dp/2)) + (cos($p1) * cos($p2) * sin($dl/2) * sin($dl/2));
        $c = 2 * atan2(sqrt($a),sqrt(1-$a));
        $r = 6371008; // Earth's average radius, in meters
        $d = $r * $c;
        return (int) round($d);
    }

    public function getDistance(): int
    {
        return $this->distance;
    }
}
