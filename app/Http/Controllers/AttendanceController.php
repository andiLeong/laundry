<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $query = Attendance::query();

        if ($user->isEmployee()) {
            $query->where('staff_id', auth()->id());
        }

        if($request->get('month') !== null){
            $start = Carbon::parse($request->get('month'));
            $query->where('time', '>=', $start)
                ->where('time', '<=', $start->copy()->endOfMonth());
        }

        if($request->get('staff_id') !== null && $user->isAdmin()){
            $query->where('staff_id', $request->get('staff_id'));
        }

        return $query->with('staff:id,first_name,middle_name,last_name','branch:id,name')->latest('id')->paginate()->through(function ($attendance){

            $attendance->branch_name = $attendance->branch->name;
            unset($attendance['branch']);
            unset($attendance['branch_id']);
            unset($attendance['staff_id']);
            unset($attendance['staff']['id']);
            return $attendance;
        });
    }
}
