<?php

namespace App\Http\Controllers;

use App\Models\Shift;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class ShiftsController extends Controller
{
    public function index(Request $request)
    {
        $shift = Shift::query();
        $user = auth()->user();
        if($user->isEmployee()){
            $shift->where('staff_id',$user->staff->id);
        }

        if($request->filled('year') && $request->filled('month')){
            $start = Carbon::parse($request->get('year') . '-' . $request->get('month'));
            $shift->where('date', '>=', $start)
                ->where('date', '<=', $start->copy()->endOfMonth());
        }else{
            $shift->currentMonth('date');
        }
        return $shift->select(['id','staff_id','from','to','date'])->get();
    }
}
