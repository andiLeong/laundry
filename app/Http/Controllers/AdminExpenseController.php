<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AdminExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::query();

        if ($request->filled('year-month')) {
            $start = Carbon::parse($request->get('year-month'));
            $query->where('created_at', '>', $start)
                ->where('created_at', '<', $start->copy()->endOfMonth());
        }

        return $query->orderBy('id', 'desc')->paginate();
    }
}
