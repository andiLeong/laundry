<?php

namespace App\Http\Controllers;

use App\Events\OrderCreated;
use App\Http\Validation\OrderValidate;
use App\Models\DeliveryFeeCalculator;
use App\Models\Enum\OrderType;
use App\Models\OnlineOrder;
use App\Models\Order;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class OrderController extends Controller
{
    public function index()
    {
        return Order::query()
            ->select(['id', 'amount', 'total_amount', 'product_amount', 'created_at', 'paid', 'payment', 'service_id'])
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->with('service:name,id')
            ->paginate();
    }

    public function store(OrderValidate $validation)
    {
        $data = $validation->validate();

        return DB::transaction(function () use ($data) {
            $user = Auth::user();
            $pickup = Carbon::parse($data['pickup']);
            $order = Order::create([
                'creator_id' => $user->id,
                'user_id' => $user->id,
                'service_id' => $data['service_id'],
                'amount' => $data['amount'],
                'total_amount' => $data['total_amount'],
                'product_amount' => $data['product_amount'],
                'delivery_fee' => $data['delivery_fee'],
                'type' => OrderType::ONLINE->value,
                'paid' => false,
                'description' => $data['description'] ?? '',
            ]);
            $onlineOrder = OnlineOrder::create([
                'order_id' => $order->id,
                'add_products' => $data['add_products'] ?? 0,
                'address_id' => $data['address_id'],
                'pickup' => $pickup,
                'delivery' => $data['delivery'] ?? $pickup->copy()->addHours(12),
            ]);

            OrderCreated::dispatch($order, $data['product']);
            return $onlineOrder;
        });
        // give default 8kg service to user, if over 8 kg we add up, if lower we reduce price
        // staff need to manually adjust the order (add/adjust current order)
        // update order weight pic , send order adjustment notification to user
        //delivery charge per load ? or per delivery

        //stage 1, customer can order online,
        // main order should have one online order only,
        // extra order should not have online order
    }

    public function show($id)
    {
        $column = [
            'id',
            'service_id',
            'amount',
            'total_amount',
            'product_amount',
            'paid',
            'payment',
            'created_at',
            'user_id'
        ];
        $order = Order::select($column)->where('id', $id)->with([
            'service:id,name',
            'products:order_id,name,quantity,price,total_price'
        ])->first();

        if (is_null($order) || $order->user_id !== auth()->id()) {
            abort(404, 'Order not found');
        }

        $order->service_name = $order->service->name;
        unset($order->service_id);
        unset($order->service);

        return $order;
    }
}
