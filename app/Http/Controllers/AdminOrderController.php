<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Service;
use Illuminate\Http\Request;

class AdminOrderController extends Controller
{
    public function store()
    {
        $user = auth()->user();
        if($user->isCustomer()){
            abort(403,'You do not have right to perform this action');
        }

        $data = request()->validate([
            'amount' => 'nullable|decimal:0,4',
            'user_id' => 'nullable|exists:users,id',
            'service_id' => 'required|exists:services,id',
        ]);

        $service = Service::find($data['service_id']);
        $data['amount'] ??= $service->price;
        return Order::create($data + ['creator_id' => $user->id]);
    }
}
