<?php

namespace App\Models;


use App\Models\Enum\AttendanceType;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SalaryCalculator
{
    private Carbon $today;

    protected $firstSalaryDay;
    protected $secondSalaryDay;

    /**
     * the salary cover period
     * @array
     */
    protected array $coverPeriod = [];

    public function __construct(protected Staff $staff)
    {
        $this->today = today();
        $this->setFirstSalaryDay();
        $this->setSecondSalaryDay();
        $this->setCoverPeriod();
    }

    public function calculate()
    {
        if ($this->salaryDay() === false) {
            return false;
        }

        $details = $this->getSalaryDetail();
        $totalSalaries = $details->sum('amount');
        [$start, $end] = $this->coverPeriod;

        $salary = Salary::create([
            'staff_id' => $this->staff->id,
            'amount' => $totalSalaries,
            'from' => $start->toDateString(),
            'to' => $end->toDateString(),
        ]);

        SalaryDetail::insert($details->map(function ($detail) use ($salary) {
            $detail['salary_id'] = $salary->id;
            return $detail;
        })->toArray());

        return true;
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

    protected function setCoverPeriod()
    {
        $this->coverPeriod =
            $this->today->day <= 15
                ? [today()->startOfMonth(), today()->startOfMonth()->addDays(15)]
                : [today()->startOfMonth()->addDays(15), today()->endOfMonth()->endOfDay()];
    }

    protected function getShifts(): Collection
    {
        [$start, $end] = $this->coverPeriod;

        return Shift::where('staff_id', $this->staff->id)
            ->with('attendance')
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->get();
    }

    protected function getSalaryDetail()
    {
        return $this->getShifts()->map(function ($shift) {

            $attendances = $shift->attendance;

            if ($attendances->isEmpty()) {
                $description = 'cant find punch detail, absence no salary of course';
                return [
                    'from' => null,
                    'to' => null,
                    'description' => $description,
                    'hour' => 0,
                    'amount' => 0,
                ];
            }

            [$in, $out] = $attendances->partition(fn($record) => $record->type === AttendanceType::in->name);
            $in->sortBy('time')->values();
            $out->sortBy('time')->values();

            if ($in->isNotEmpty() && $out->isNotEmpty()) {
                $start = $in->first()->time;
                $end = $out->last()->time;
                $hour = $start->diffInHours($end);
                [$description, $amount] = $this->getSalaryForHours($hour, $shift);

                return [
                    'from' => $start,
                    'to' => $end,
                    'description' => $description,
                    'hour' => $hour,
                    'amount' => $amount,
                ];
            }

            $hour = $shift->from->diffInHours($shift->to);
            [$description, $amount] = $this->getSalaryForHours($hour, $shift);
            if ($in->isEmpty()) {
                $end = $out->first()->time;
                $start = null;
                $description = 'no punch in detail, get 8 hour salary';
            } else {
                $end = null;
                $start = $in->first()->time;
                $description = 'no punch out detail, get 8 hour salary';
            }

            return [
                'from' => $start,
                'to' => $end,
                'description' => $description,
                'hour' => $hour,
                'amount' => $amount,
            ];

        });
    }

    private function getSalaryForHours(mixed $hour, $shift)
    {
        $perDay = $this->staff->daily_salary;
        $date = $shift->date;

        $shouldWorkHour = $shift->from->diffInHours($shift->to);
        if ($hour < $shouldWorkHour) {
            $description = "working hour is $hour less than shift hour $shouldWorkHour no salary";
            $amount = 0;
        } elseif ($hour >= 4 && $hour < 8) {
            $description = 'working hour is between 4 to 8 hours, half day salary';
            $amount = $perDay / 2;
        } elseif ($hour >= 8 && $hour < 12) {
            [$holidayDescription, $amount] = $this->getHolidaySalary($date, $perDay);
            $description = 'working hour is between 8 - 12 hour, normal daily salary' . $holidayDescription;
        } elseif ($hour >= 12) {
            $perDay = $perDay + 100;
            [$holidayDescription, $amount] = $this->getHolidaySalary($date, $perDay);
            $description = 'working hour is euq or more than 12 hours, add 100 currently' . $holidayDescription;
        } else {
            $description = "hour is $hour, unknown condition";
            $amount = 0;
        }

        return [
            $description,
            round($amount, 2),
        ];
    }

    protected function getHolidaySalary($date, $perDay)
    {
        $holiday = Holiday::where('date', $date)->first();
        $holidayDescription = '';
        $amount = $perDay;
        if (!is_null($holiday)) {
            $holidayDescription = ' with holiday rate ' . round($holiday->rate, 1);
            $amount = $perDay + $perDay * $holiday->rate;
        }

        return [$holidayDescription, $amount];
    }

}
