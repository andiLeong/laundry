<?php

namespace App\Console\Commands;

use App\Models\SalaryCalculation\SalaryCalculator;
use App\Models\Staff;
use Illuminate\Console\Command;

class CalculateSalary extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'salary:calculate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Calculate Staff Salary';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        logger('calculate staff salary');

        Staff::with('user')
            ->get()
            ->each(function(Staff $staff){

                logger('calculating staff: ' . $staff->user->first_name);
                $calculator = new SalaryCalculator($staff);
                $calculator->calculate();

            });

        logger('calculate staff salary finished');
    }
}
