<?php

namespace App\Http\Controllers;

use App\Events\OnlineOrderStatusUpdated;
use App\Models\OnlineOrder;
use Exception;
use Illuminate\Http\Request;

class OnlineOrderStatusController extends Controller
{
    public function update($id, Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:delivery,pickup',
            'image' => 'nullable|array|max:2',
            'image.*' => 'image|max:2048',
        ]);

        $onlineOrder = OnlineOrder::where('order_id', $id)->first();
        if (is_null($onlineOrder)) {
            abort(404, 'Order is not existed');
        }

        if ($onlineOrder->isDelivered()) {
            abort(400, 'Order is delivered, you do not need to update');
        }

        $method = $validated['type'] == 'pickup' ? 'markAsPickup' : 'markAsDelivered';
        try {
            $onlineOrder->{$method}();
        } catch (Exception $e) {
            abort(400, $e->getMessage());
        }

        OnlineOrderStatusUpdated::dispatch($onlineOrder);
        return ['message' => 'success'];
    }
}
