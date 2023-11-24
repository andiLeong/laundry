<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Enum\AttendanceType;
use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Attendance::query();

        if ($user->isEmployee()) {
            $query->where('staff_id', $user->staff->id);
        }

        if ($request->get('month') !== null) {
            $start = Carbon::parse($request->get('month'));
            $query->where('time', '>=', $start)
                ->where('time', '<=', $start->copy()->endOfMonth());
        }

        if ($request->get('staff_id') !== null && $user->isAdmin()) {
            $query->where('staff_id', $request->get('staff_id'));
        }

        return $query
            ->with(['staff:id,user_id' => ['user:id,first_name,middle_name,last_name'], 'branch:id,name'])
            ->latest('id')
            ->paginate()
            ->through(function ($attendance) {
                $attendance->branch_name = $attendance->branch->name;
                $attendance->staff->first_name = $attendance->staff->user->first_name;
                $attendance->staff->middle_name = $attendance->staff->user->middle_name;
                $attendance->staff->last_name = $attendance->staff->user->last_name;
                unset($attendance['branch']);
                unset($attendance['branch_id']);
                unset($attendance['staff_id']);
                unset($attendance['staff']['user']);
                unset($attendance['shift_id']);
                return $attendance;
            });
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:' . AttendanceType::in->value . ',' . AttendanceType::out->value,
            'longitude' => 'required',
            'latitude' => 'required',
        ]);

        $staff = auth()->user()->staff;

        $shift = Shift::where('date', today()->toDateTimeString())->where('staff_id', $staff->id)->first();
        if (is_null($shift)) {
            abort(400, 'You are not supposed to work today..');
        }

        if (Attendance::outOfRange($validated['latitude'], $validated['longitude'], $staff->branch_id)) {
            abort(400, 'Your location seems too far from your branch');
        }

        return Attendance::create([
            'staff_id' => $staff->id,
            'time' => now(),
            'type' => $validated['type'],
            'branch_id' => $staff->branch_id,
            'shift_id' => $shift->id,
        ]);
    }
}
