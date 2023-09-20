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

        foreach ($shifts as $shift) {

            $punchDuringShift = Attendance::where('staff_id', $shift->staff_id)
                ->whereBetween('time', [$shift->from, $shift->to])
                ->get();

            [$punchInDuringShift, $punchOutDuringShift] = $punchDuringShift->partition(function ($punch) {
                return $punch->type === AttendanceType::in->name;
            });

            $punchInBeforeShift = Attendance::where('staff_id', $shift->staff_id)
                ->where('type', AttendanceType::in->value)
                ->where('time', '<=', $shift->from)
                ->where('time', '>=', $shift->from->copy()->subHours(Shift::TIME_SPAN))
                ->get();

            $punchOutAfterShift = Attendance::where('staff_id', $shift->staff_id)
                ->where('type', AttendanceType::out->value)
                ->where('time', '>=', $shift->to)
                ->where('time', '<=', $shift->to->copy()->addHours(Shift::TIME_SPAN))
                ->get();

            $updates = [];
            if ($punchInBeforeShift->isEmpty()) {
                if ($punchInDuringShift->isEmpty()) {
                    $updates['absence'] = true;
                } else {
                    $updates['late'] = true;
                }
            }

            if ($punchOutDuringShift->isNotEmpty() && $punchOutAfterShift->isEmpty()) {
                $updates['early_leave'] = true;
            }

            $shift->update(array_merge(
                ['reviewed' => true], $updates
            ));
        }

    }
}
