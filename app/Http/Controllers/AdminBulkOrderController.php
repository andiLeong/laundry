<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Http\Validation\AdminCreateBulkOrderValidation;
use App\Models\Order;
use Illuminate\Http\Request;

class AdminBulkOrderController extends Controller
{
    public function __invoke(AdminCreateBulkOrderValidation $validator, Request $request)
    {
        $logInUser = auth()->user();
        $data = $validator->validate();

        foreach (range(1, $request->get('build')) as $v) {
            tap(Order::create($data + ['creator_id' => $logInUser->id,]),
                function ($order) {
                    OrderCreated::dispatch($order);
                });
        }
    }
}
