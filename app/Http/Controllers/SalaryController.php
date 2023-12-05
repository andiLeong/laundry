<?php

namespace App\Http\Controllers;

use App\Models\Salary;

class SalaryController extends Controller
{
    public function index()
    {
        $query = Salary::query();

        $user = auth()->user();
        if ($user->isEmployee()) {
            $query->where('staff_id', $user->staff->id);
        }

        return $query->with('staff:id,user_id','staff.user:id,first_name')->paginate();
    }
}
