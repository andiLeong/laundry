<?php

namespace App\Http\Controllers;

use App\Models\Salary;
use App\Models\SalaryDetail;

class SalaryDetailController extends Controller
{
    public function index($id)
    {
        $user = auth()->user();
        $salary = Salary::where('id', $id)->first();
        if (is_null($salary)) {
            abort(404, 'record not found');
        }

        if ($user->isEmployee() && $salary->staff_id !== $user->staff->id) {
            abort(404, 'hey this is not your record');
        }

        return SalaryDetail::with('shift:id,date,from,to')->where('salary_id', $id)->orderBy('from')->paginate();
    }
}
