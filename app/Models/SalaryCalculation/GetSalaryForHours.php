<?php

namespace App\Models\SalaryCalculation;

use App\Models\Holiday;
use App\Models\Shift;

class GetSalaryForHours
{
    private Shift $shift;
    private $hour;
    private $perday;
    private $shouldWorkHour;
    private mixed $date;

    public function __construct(Shift $shift, $hour, $perday)
    {
        $this->shift = $shift;
        $this->date = $shift->date;
        $this->hour = $hour;
        $this->perday = $perday;
        $this->shouldWorkHour = $this->shift->from->diffInHours($this->shift->to);
    }

    public function get()
    {
        $conditions = [
            'lessThanWorkHourSalary' => fn() => $this->hour < $this->shouldWorkHour,
            'halfDaySalary' => fn() => $this->hour >= 4 && $this->hour < 8,
            'fullDaySalary' => fn() => $this->hour >= 8 && $this->hour < 12,
            'extraFullDaySalary' => fn() => $this->hour >= 12,
        ];

        foreach ($conditions as $method => $condition) {
            if (call_user_func($condition)) {
                return $this->{$method}();
            }
        }
    }

    protected function lessThanWorkHourSalary()
    {
        return ["working hour is $this->hour less than shift hour $this->shouldWorkHour no salary", 0];
    }

    protected function halfDaySalary()
    {
        return ['working hour is between 4 to 8 hours, half day salary', round($this->perday / 2, 2)];
    }

    protected function fullDaySalary()
    {
        [$holidayDescription, $amount] = $this->getHolidaySalary($this->date, $this->perday);
        $description = 'working hour is between 8 - 12 hour, normal daily salary' . $holidayDescription;
        return [$description, round($amount, 2)];
    }

    protected function extraFullDaySalary()
    {
        $perDay = $this->perday + 100;
        [$holidayDescription, $amount] = $this->getHolidaySalary($this->date, $perDay);
        $description = 'working hour is euq or more than 12 hours, add 100 currently' . $holidayDescription;
        return [
            $description,
            round($amount, 2),
        ];
    }

    protected function getHolidaySalary($date, $perDay, $hour = 8)
    {
        $holiday = Holiday::where('date', $date)->first();
        $holidayDescription = '';
        $amount = $perDay;
        if (!is_null($holiday) && in_array($hour, [8, 12])) {
            $holidayDescription = ' with holiday rate ' . round($holiday->rate, 1);
            $amount = $perDay + $perDay * $holiday->rate;
        }

        return [$holidayDescription, $amount];
    }
}
