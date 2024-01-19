<?php

namespace App\Http\Controllers;

use App\Models\Enum\OnlineOrderStatus;
use App\Models\OnlineOrder;
use Exception;
use Illuminate\Http\Request;

class OnlineOrderStatusController extends Controller
{
    //
    public function update($id, Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:delivery,pickup'
        ]);

        $onlineOrder = OnlineOrder::where('order_id', $id)->first();
        if (is_null($onlineOrder)) {
            abort(404, 'Order is not existed');
        }

        if ($onlineOrder->isDelivied()) {
            abort(400, 'Order is delivered, you do not need to update');
        }

        $method = $validated['type'] == 'pickup' ? 'markAsPickup' : 'markAsDelivered';
        try {
            $onlineOrder->{$method}();
        } catch (Exception $e) {
            abort(400, $e->getMessage());
        }

        return ['message' => 'success'];
    }
}
