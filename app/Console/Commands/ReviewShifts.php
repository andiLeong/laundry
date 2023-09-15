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

        $shifts = Shift::unreviewed()->get();

        foreach ($shifts as $index => $shift) {

            $punchInDuringShift = Attendance::where('staff_id', $shift->staff_id)
                ->where('type', AttendanceType::in->value)
                ->whereBetween('time', [$shift->from, $shift->to])
                ->get();

            $punchInBeforeShift = Attendance::where('staff_id', $shift->staff_id)
                ->where('type', AttendanceType::in->value)
                ->where('time', '<=', $shift->from)
                ->where('time', '>=', $shift->from->copy()->subHours(3))
                ->get();

            $updates = [];
            if ($punchInBeforeShift->isEmpty()) {
                if ($punchInDuringShift->isEmpty()) {
                    $updates['absence'] = true;
                } else {
                    $updates['late'] = true;
                }
            }

            $shift->update(array_merge(
                ['reviewed' => true], $updates
            ));
        }

    }
}
