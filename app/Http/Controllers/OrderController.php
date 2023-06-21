<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function store()
    {
        $user = auth()->user();
        if($user->isCustomer()){
            abort(403,'You do not have right to perform this action');
        }

        $data = request()->validate([
            'amount' => 'required|decimal:0,4',
            'user_id' => 'nullable|exists:users,id',
        ]);

        return Order::create($data);
    }
}
