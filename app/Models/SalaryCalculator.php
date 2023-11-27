<?php

namespace App\Models;


use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SalaryCalculator
{
    const MIN_WORK_HOUR = 8;
    private Carbon $today;

    protected $firstSalaryDay;
    protected $secondSalaryDay;

    protected $coverPeriod;
    private $firstHalfSalary = false;

    public function __construct(protected Staff $staff)
    {
        $this->today = today();
        $this->setFirstSalaryDay();
        $this->setSecondSalaryDay();
        $this->setFirstHalfSalary();
    }

    public function calculate()
    {
        if ($this->salaryDay() === false) {
            return false;
        }

        $details = $this->getSalaryDetail();

//        [
//            '2023-11-23' => [
//                'start' => '2023-11-23 08:00',
//                'end' => '2023-11-23 20:00',
//                'hour' => 12,
//            ]
//        ];
//        $details = $this->getAttendance()
//            ->map(function ($attendance) {
//
//                return [
//
//                    'amount' => $hour > static::MIN_WORK_HOUR
//                        ? $this->staff->daily_salary + 100
//                        : $this->staff->daily_salary,
//                ];
//            });

    }

    protected function salaryDay(): bool
    {
        return in_array($this->today->day, [$this->firstSalaryDay, $this->secondSalaryDay]);
    }

    private function setFirstSalaryDay(): void
    {
        $firstSalary = $this->today->copy()->firstOfMonth()->addDays(14);
        while (true) {
            if ($firstSalary->isWeekend()) {
                $firstSalary->subDay();
            } else {
                break;
            }
        }

        $this->firstSalaryDay = $firstSalary->day;
    }

    private function setSecondSalaryDay(): void
    {
        $secondSalary = $this->today->copy()->endOfMonth();
        while (true) {
            if ($secondSalary->isWeekend()) {
                $secondSalary->subDay();
            } else {
                break;
            }
        }
        $this->secondSalaryDay = $secondSalary->day;
    }

    protected function setFirstHalfSalary()
    {
//        $this->coverPeriod =
//            $this->today->day <= 15
//                ? [1, 16]
//                : [17, $this->today->endOfMonth()->day];

        if ($this->today->day <= 15) {
            $this->firstHalfSalary = true;
        }
    }

    private function getShifts(): Collection
    {
        if ($this->firstHalfSalary) {
            $start = today()->startOfMonth();
            $end = today()->startOfMonth()->addDays(15);
        } else {
            $start = today()->startOfMonth()->addDays(15);
            $end = today()->endOfMonth()->endOfDay();
        }

        return Shift::where('staff_id', $this->staff->id)
            ->with('attendance')
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->get();
    }

    public function getSalaryDetail()
    {
        $shifts = $this->getShifts();
        return $shifts->map(function ($shift) {

            $attendances = $shift->attedance;
            $attendances->sortBy('type')->values()->sortBy('time')->values();

            if (empty($attendances)) {
                $amount = 0;
                $description = 'cant find punch detail, absence no salary of course';
                $hour = 0;
                $start = $end = null;
            } elseif (count($attendances) === 1) {
                $attendance = $attendances->first();
                $hour = 0;
                if ($attendance->type == 1) {
                    $start = $attendance->time;
                    $end = null;
                } else {
                    $start = null;
                    $end = $attendance->time;
                }
                [$description, $amount] = $this->getSalaryForHours(8, $shift->date);
            } else {
                $start = $attendances->first()->time;
                $end = $attendances->last()->time;
                $hour = $start->diffInHours($end);
                [$description, $amount] = $this->getSalaryForHours($hour, $shift->date);
            }

            return [
                'date' => $shift->date,
                'from' => $start,
                'to' => $end,
                'description' => $description,
                'hour' => $hour,
                'amount' => $amount,
            ];
        });
    }

    private function getSalaryForHours(mixed $hour, $date)
    {
        $holiday = Holiday::where('date', $date)->first();
        $rate = $holiday?->rate ?? 1;

        if ($hour >= 4 && $hour < 8) {
            $description = 'working hour is between 4 to 8 hours, half day salary';
            $amount = $this->staff->daily_salary / 2;
        } elseif ($hour >= 8 && $hour < 12) {
            $description = 'working hour is between 8 - 12 hour, normal daily salary';
            $amount = $this->staff->daily_salary * $rate;
        } elseif ($hour >= 12) {
            $description = 'working hour is euq or more than 12 hours, add 100 currently';
            $amount = ($this->staff->daily_salary + 100) * $rate;
        } else {
            $description = "hour is $hour, unknown condition";
            $amount = 0;
        }

        return [
            $description,
            round($amount, 2),
        ];
    }

}
