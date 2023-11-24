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

    private function getAttendance(): Collection
    {
        if ($this->firstHalfSalary) {
            $attendance = Attendance::where('staff_id', $this->staff->id)
                ->where('time', '>=', today()->startOfMonth())
                ->where('time', '<=', today()->startOfMonth()->addDays(15))
                ->get();
        } else {
            $attendance = Attendance::where('staff_id', $this->staff->id)
                ->where('time', '>=', today()->startOfMonth()->addDays(15))
                ->where('time', '<=', today()->endOfMonth()->endOfDay())
                ->get();
        }

//        return $attendance->groupBy('date')->map(function ($record) {
//
//            $record = collect($record)->sortBy('time');
//            $first = $record->first();
//            $last = $record->last();
//            $hour = ;
//            return [
//                'hour' => $hour,
//            ]
//        });
    }

}
