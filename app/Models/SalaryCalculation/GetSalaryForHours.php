<?php

namespace App\Models\SalaryCalculation;

use App\Models\Holiday;
use App\Models\Shift;
use Illuminate\Support\Str;

class GetSalaryForHours
{
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

    public function __construct(protected Shift $shift, protected $hour, protected $perday)
    {
        $this->date = $shift->date;
        $this->shouldWorkHour = $this->shift->from->diffInHours($this->shift->to);
        $this->holiday = Holiday::where('date', $this->date)->first();
    }

    /**
     * get salary on a given a working hour
     * @return array
     */
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

    /**
     * get salary on a without work
     * @return array
     */
    public function getSalaryWithoutPay(): array
    {
        [$description, $amount] = $this->get();
        $description .= " salary without works on " . $this->date->toDateString();
        return [$description, $amount];
    }

    /**
     * get salary amount and description if working hour is less than salary
     * @return array
     */
    protected function lessThanShiftHourSalary(): array
    {
        return [
            Str::replaceArray('?', [$this->hour, $this->shouldWorkHour], static::DESCRIPTION[0]),
            0
        ];
    }

    /**
     * get salary amount and description on a half day shift
     * @return array
     */
    protected function halfDaySalary(): array
    {
        return [static::DESCRIPTION[4], round($this->perday / 2, 2)];
    }

    /**
     * get salary amount and description on a full day shift / 8 hours
     * @return array
     */
    protected function fullDaySalary(): array
    {
        [$description, $amount] = $this->getHolidaySalary(static::DESCRIPTION[8]);
        return [$description, round($amount, 2)];
    }

    /**
     * get salary amount and description on a extra full day shift / 12 hours
     * @return array
     */
    protected function extraFullDaySalary(): array
    {
        $this->perday = $this->perday + 100;
        [$description, $amount] = $this->getHolidaySalary(static::DESCRIPTION[12]);
        return [
            $description,
            round($amount, 2),
        ];
    }

    /**
     * get holiday salary
     * @param $description
     * @return array
     */
    protected function getHolidaySalary($description): array
    {
        $perDay = $this->perday;

        $amount = $perDay;
        if ($this->entitledForHolidayPay()) {
            $description .= ' with holiday rate ' . round($this->holiday->rate, 1);
            $amount = $perDay + $perDay * $this->holiday->rate;
        }

        return [$description, $amount];
    }

    /**
     * determine if the shift's date can entitle for holiday pay
     * @return bool
     */
    protected function entitledForHolidayPay(): bool
    {
        return $this->hour >= static::HOLIDAY_PAY_REQUIRE_WORKING_HOURS && $this->holiday !== null;
    }
}
