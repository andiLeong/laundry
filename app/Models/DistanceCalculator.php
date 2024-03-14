<?php

namespace App\Models;

class DistanceCalculator
{
    public function __construct(protected readonly Coordinate $from, protected readonly Coordinate $to)
    {
        //
    }

    public function calculate(): int
    {
        $p1 = deg2rad($this->to->latitude);
        $p2 = deg2rad($this->from->latitude);
        $dp = deg2rad($this->from->latitude - $this->to->latitude);
        $dl = deg2rad($this->from->longitude - $this->to->longitude);
        $a = (sin($dp/2) * sin($dp/2)) + (cos($p1) * cos($p2) * sin($dl/2) * sin($dl/2));
        $c = 2 * atan2(sqrt($a),sqrt(1-$a));
        $r = 6371008; // Earth's average radius, in meters
        $d = $r * $c;
        return (int) round($d);
    }
}
