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
            ->where('id', Auth::id())
            ->orderBy('id', 'desc')
            ->with('service:name,id')
            ->paginate();
    }
}
