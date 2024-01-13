<?php

namespace App\Http\Controllers;

use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class OrderController extends Controller
{
    public function index()
    {
        return Order::query()
            ->select(['id','amount','total_amount','product_amount','created_at','paid','payment','service_id'])
            ->where('user_id', Auth::id())
            ->orderBy('id', 'desc')
            ->with('service:name,id')
            ->paginate();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
//            'payment' => 'required|in:1',
            'promotion_ids' => 'nullable|array',
            'address_id' => 'required',
            'product_ids' => 'nullable|array',
            'delivery' => 'nullable|datetime',
            'pickup' => 'required|datetime',
            'product_apply_each_load' => 'required_if:product_ids|boolean',
        ]);

        // give default 8kg service to user, if over 8 kg we add up, if lower we reduce price
        // staff need to manually adjust the order (add/adjust current order)
        // update order weight pic , send order adjustment notification to user

        //delivery charge per load ? or per delivery
    }

    public function show($id)
    {
        $column = ['id','service_id','amount','total_amount','product_amount','paid','payment','created_at','user_id'];
        $order = Order::select($column)->where('id',$id)->with(['service:id,name','products:order_id,name,quantity,price,total_price'])->first();

        if(is_null($order) ||  $order->user_id !== auth()->id()){
            abort(404,'Order not found');
        }

        $order->service_name = $order->service->name;
        unset($order->service_id);
        unset($order->service);

        return $order;
    }
}
