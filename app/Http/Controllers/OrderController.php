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

    public function show($id)
    {
        $column = ['id','service_id','amount','total_amount','product_amount','paid','payment','created_at','user_id'];
        $order = Order::select($column)->where('id',$id)->with(['service:id,name','productOrder:name,price'])->first();

        if(is_null($order) ||  $order->user_id !== auth()->id()){
            abort(404,'Order not found');
        }

        $order->productOrder->map(function($product){
            $product['quantity'] = $product->pivot->quantity;
            unset($product->pivot);
            return $product;
        });
        $order->service_name = $order->service->name;
        unset($order->service_id);
        unset($order->service);

        return $order;
    }
}
