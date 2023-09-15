<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Enum\AttendanceType;
use App\Models\Shift;
use Illuminate\Console\Command;

class ReviewShifts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'shift:review';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'mark a shift as late , absence , or early off';

    /**
     * Execute the console command.
     */
    public function handle()
    {

        $shifts = Shift::get();

        foreach ($shifts as $index => $shift) {

            $attendance = Attendance::where('staff_id', $shift->staff_id)
                ->where('type', AttendanceType::in->value)
                ->whereBetween('time', [$shift->from, $shift->end])
                ->get();

            $lastShiftEnd = $shift->date->subDay();
            $lastShift = Shift::where('date',$lastShiftEnd->toDateString())->first();
            if($lastShift !== null){
                $lastShiftEnd = $lastShift->to;
            }
//            dump($lastShiftEnd);
            $beforeAttendance = Attendance::where('staff_id', $shift->staff_id)
                ->where('type', AttendanceType::in->value)
                ->where('time', '<=', $shift->from)
                ->where('time', '>=', $lastShiftEnd)
                ->get();

            $beforeShiftStart = false;
            if($beforeAttendance->isNotEmpty()){
                $beforeShiftStart = true;
            }
            if(!empty($attendance) && $beforeShiftStart === false){
                $shift->update(['late' => true]);
            }
        }

//        dd($shifts);

    }
}
