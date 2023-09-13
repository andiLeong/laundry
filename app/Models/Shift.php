<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'days' => 'array',
        'off' => 'array',
    ];

    /**
     * check a shift is late
     * @param $dateTime
     * @return bool
     */
    public function lateOn($dateTime = null): bool
    {
        $now = $dateTime ?? now();
        if ($this->match($now) && $this->isLate($now)) {
            return true;
        }
        return false;
    }

    /**
     * check if this shift is considered late on a given date
     * @param $date
     * @return mixed
     */
    protected function isLate($date): bool
    {
        $from = explode(':', $this->from);
        $startToWork = $date->copy()->hour($from[0])->minute($from[1]);
        return $date->gt($startToWork);
    }

    /**
     * check a given datetime is working on this shift
     * @param $date
     * @return bool
     */
    protected function match($date): bool
    {
        $day = $date->dayOfWeekIso;
        return in_array($day, $this->days);
    }
}
