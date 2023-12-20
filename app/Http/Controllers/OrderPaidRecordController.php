<?php

namespace App\Http\Controllers;

use App\Models\OrderPaid;
use Illuminate\Http\Request;

class OrderPaidRecordController extends Controller
{
    public function index(Request $request)
    {
        $record = OrderPaid::query();
        if ($request->filled('start') && $request->filled('end')) {
            $record->createBetween($request->get('start'), $request->get('end'));
        } else {
            $record->today();
        }

        if ($request->filled('payment')) {
            $record->whereHas('order', fn($query) => $query->where('payment', $request->get('payment')));
        }

        $res = $record->with('creator:id,first_name', 'order:id,payment,description')->get();
        return [
            'data' => $res,
            'total'=> $res->sum('amount'),
        ];
    }
}
