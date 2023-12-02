<?php

namespace App\Models\SalaryCalculation;

use App\Models\Holiday;
use App\Models\Shift;
use Illuminate\Support\Str;

class GetSalaryForHours
{
    private Shift $shift;
    private $hour;
    private $perday;
    private $shouldWorkHour;
    private mixed $date;
    private $holiday;

    const HOLIDAY_PAY_REQUIRE_WORKING_HOURS = 8;
    const DESCRIPTION = [
        0 => 'working hour is ? less than shift hour ? no salary',
        4 => 'working hour is between 4 to 8 hours, half day salary',
        8 => 'working hour is between 8 - 12 hour, normal daily salary',
        12 => 'working hour is euq or more than 12 hours, add 100 currently',
    ];

    public function __construct(Shift $shift, $hour, $perday)
    {
        $this->shift = $shift;
        $this->date = $shift->date;
        $this->hour = $hour;
        $this->perday = $perday;
        $this->shouldWorkHour = $this->shift->from->diffInHours($this->shift->to);
        $this->holiday = Holiday::where('date', $this->date)->first();
    }

    public function get()
    {
        $conditions = [
            'lessThanShiftHourSalary' => fn() => $this->hour < $this->shouldWorkHour,
            'halfDaySalary' => fn() => $this->hour >= 4 && $this->hour < 8,
            'fullDaySalary' => fn() => $this->hour >= 8 && $this->hour < 12,
            'extraFullDaySalary' => fn() => $this->hour >= 12,
        ];

        foreach ($conditions as $method => $condition) {
            if (call_user_func($condition)) {
                return $this->{$method}();
            }
        }

        return ["hour is $this->hour, unknown condition", 0];
    }

    public function getSalaryWithoutPay(): array
    {
        [$description, $amount] = $this->get();
        $description .= " salary without works on " . $this->date->toDateString();
        return [$description, $amount];
    }

    protected function lessThanShiftHourSalary(): array
    {
        return [
            Str::replaceArray('?', [$this->hour, $this->shouldWorkHour], static::DESCRIPTION[0]),
            0
        ];
    }

    protected function halfDaySalary(): array
    {
        return [static::DESCRIPTION[4], round($this->perday / 2, 2)];
    }

    protected function fullDaySalary(): array
    {
        [$description, $amount] = $this->getHolidaySalary(static::DESCRIPTION[8]);
        return [$description, round($amount, 2)];
    }

    protected function extraFullDaySalary(): array
    {
        $this->perday = $this->perday + 100;
        [$description, $amount] = $this->getHolidaySalary(static::DESCRIPTION[12]);
        return [
            $description,
            round($amount, 2),
        ];
    }

    protected function getHolidaySalary($description): array
    {
        $perDay = $this->perday;

        $amount = $perDay;
        if ($this->qualifyForHolidayPay()) {
            $description .= ' with holiday rate ' . round($this->holiday->rate, 1);
            $amount = $perDay + $perDay * $this->holiday->rate;
        }

        return [$description, $amount];
    }

    protected function qualifyForHolidayPay(): bool
    {
        return $this->hour >= static::HOLIDAY_PAY_REQUIRE_WORKING_HOURS && $this->holiday !== null;
    }
}
