<?php

namespace App\Models;


use Illuminate\Support\Carbon;
use function Laravel\Prompts\password;

class SalaryCalculator
{
    private Carbon $today;

    protected $firstSalaryDay;
    protected $secondSalaryDay;

    protected $coverPeriod;

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

        $attendance = $this->getAttendance();

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
                ? [1, 16]
                : [17, $this->today->endOfMonth()->day];
    }

    private function getAttendance()
    {
        Attendance::where('staff_id', $this->staff->id)
                ->where('time','>=')
            ->where('time','<=')
        ;

    }

}
