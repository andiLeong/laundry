<?php

namespace App\Models;


use Illuminate\Support\Carbon;

class SalaryCalculator
{
    CONST MONTHLY_FIRST_SALARY_DAY = 15;

    private Carbon $today;

    protected $firstSalaryDay;
    protected $secondSalaryDay;

    public function __construct(protected User $staff)
    {
        $this->today = today();
        $this->setFirstSalaryDay();
        $this->setSecondSalaryDay();
    }

    public function calculate()
    {

    }

    protected function salaryDay() :bool
    {
        //if current month salary day falls on weekend or holiday
        //a day before should count as salary day

        if($this->today->isWeekend()){
            return false;
        }

        if($this->today->copy()->endOfMonth()->isToday() || $this->today->day === static::MONTHLY_FIRST_SALARY_DAY){
            return true;
        }

        return false;
    }

    private function setFirstSalaryDay()
    {
        $firstSalary = $this->today->copy()->firstOfMonth()->addDays(14);
        while(true){
            if($firstSalary->isWeekend()){
               $firstSalary->subDay();
            }else{
                break;
            }
        }

        $this->firstSalaryDay = $firstSalary->day;
    }

    private function setSecondSalaryDay()
    {
        $secondDaySalary = $this->today->copy()->endOfMonth();
        while(true){
            if($secondDaySalary->isWeekend()){
               $secondDaySalary->subDay();
            }else{
                break;
            }
        }
        $this->secondSalaryDay = $secondDaySalary->day;
    }

}
