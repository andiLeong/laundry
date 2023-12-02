<?php

namespace App\Models\SalaryCalculation;


use App\Models\Enum\AttendanceType;
use App\Models\Holiday;
use App\Models\Salary;
use App\Models\SalaryDetail;
use App\Models\Shift;
use App\Models\Staff;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class SalaryCalculator
{
    private Carbon $today;

    protected $firstSalaryDay;
    protected $secondSalaryDay;

    /**
     * contains the day that can get paid without actually working
     * @var array
     */
    protected array $getPaidWithoutWork = [];

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
            if ($firstSalary->isWeekend() || Holiday::where('date',$firstSalary)->first() !== null) {
                $this->getPaidWithoutWork[] = $firstSalary->day;
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
//        dd($secondSalary);
//        $foo = Holiday::where('date',$secondSalary)->first();
//        dump($foo->toArray());
        while (true) {
            if ($secondSalary->isWeekend() || Holiday::where('date',$secondSalary)->first() !== null) {
                $this->getPaidWithoutWork[] = $secondSalary->day;
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
        return $this
            ->getShifts()
            ->map(function (Shift $shift) {
                $attendances = $shift->attendance;
                if ($attendances->isEmpty()) {
                    if ($this->getPaidWithoutWork($shift->date)) {
                        $salary = new GetSalaryForHours($shift, $shift->from->diffInHours($shift->to), $this->staff->daily_salary);
                        [$description, $amount] = $salary->getSalaryWithoutPay();
                    } else {
                        $description = 'cant find punch detail, absence no salary of course';
                        $amount = 0;
                    }

                    return [
                        'from' => null,
                        'to' => null,
                        'description' => $description,
                        'hour' => 0,
                        'amount' => $amount,
                        'shift_id' => $shift->id,
                    ];
                }

                [$in, $out] = $attendances->partition(fn($record) => $record->type === AttendanceType::in->name);
                $start = $in->sortBy('time')->values()->first()?->time;
                $end = $out->sortBy('time')->values()->last()?->time;
                return $this->calculateSalary($shift, $start, $end);
            });
    }

    private function getPaidWithoutWork(\Carbon\Carbon $date): bool
    {
        return in_array($date->day, $this->getPaidWithoutWork);
    }

    private function calculateSalary(Shift $shift, $start, $end)
    {
        if (!is_null($start) && !is_null($end)) {
            $hour = $start->diffInHours($end);
        } else {
            $hour = $shift->from->diffInHours($shift->to);
        }

        [$description, $amount] = (new GetSalaryForHours($shift, $hour, $this->staff->daily_salary))->get();
        if (is_null($start)) {
            $description = 'no punch in detail, get 8 hour salary';
        }
        if (is_null($end)) {
            $description = 'no punch out detail, get 8 hour salary';
        }

        return [
            'from' => $start,
            'to' => $end,
            'description' => $description,
            'hour' => $hour,
            'amount' => $amount,
            'shift_id' => $shift->id,
        ];
    }

}
