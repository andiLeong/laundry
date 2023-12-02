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
        $this->firstSalaryDay = $this->setSalaryDate($this->today->copy()->firstOfMonth()->addDays(14))->day;
        $this->secondSalaryDay = $this->setSalaryDate($this->today->copy()->endOfMonth())->day;
        $this->setCoverPeriod();
    }

    /**
     * calculate staff salary based on salary period
     * @return bool
     */
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

    /**
     * determine if today we should calculate salary
     * @return bool
     */
    protected function salaryDay(): bool
    {
        return in_array($this->today->day, [$this->firstSalaryDay, $this->secondSalaryDay]);
    }

    /**
     * change the given date to a salary day
     * @param Carbon $dt
     * @return Carbon
     */
    protected function setSalaryDate(Carbon $dt)
    {
        while (true) {
            if ($this->isNotSalaryDate($dt)) {
                $this->getPaidWithoutWork[] = $dt->day;
                $dt->subDay();
            } else {
                break;
            }
        }
        return $dt;
    }

    /**
     * set the salary coverage period
     */
    protected function setCoverPeriod()
    {
        $this->coverPeriod =
            $this->today->day <= 15
                ? [today()->startOfMonth(), today()->startOfMonth()->addDays(15)]
                : [today()->startOfMonth()->addDays(15), today()->endOfMonth()->endOfDay()];
    }

    /**
     * determine the given date is holiday or weekend
     * @param Carbon $dt
     * @return bool
     */
    protected function isNotSalaryDate(Carbon $dt)
    {
        return $dt->isWeekend() || Holiday::where('date', $dt->toDateString())->first() !== null;
    }

    /**
     * get all shifts that's on the salary cover period
     * @return Collection
     */
    protected function getShifts(): Collection
    {
        [$start, $end] = $this->coverPeriod;

        return Shift::where('staff_id', $this->staff->id)
            ->with('attendance')
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->get();
    }

    /**
     * get all salary details for all shifts
     * @return Collection|\Illuminate\Support\Collection
     */
    protected function getSalaryDetail()
    {
        return $this
            ->getShifts()
            ->map(function (Shift $shift) {
                $attendances = $shift->attendance;
                if ($attendances->isEmpty()) {
                    if ($this->getPaidWithoutWork($shift->date)) {
                        $salary = new GetSalaryForHours($shift, $shift->from->diffInHours($shift->to),
                            $this->staff->daily_salary);
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

    /**
     * check the given date is on the ger pay without work period
     * @param Carbon $date
     * @return bool
     */
    private function getPaidWithoutWork(Carbon $date): bool
    {
        return in_array($date->day, $this->getPaidWithoutWork);
    }

    /**
     * calculate the shift's salary
     * @param Shift $shift
     * @param $start
     * @param $end
     * @return array
     */
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
