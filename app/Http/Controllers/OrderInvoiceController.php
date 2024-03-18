<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderInvoice;
use Illuminate\Http\Request;

class OrderInvoiceController extends Controller
{
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'order_id' => 'required|unique:order_invoices',
            'invoice_id' => 'required',
            'amount' => 'required|int',
        ]);
        $order = Order::find($attributes['order_id']);
        if (is_null($order)) {
            abort(400, 'Order id ' . $attributes['order_id'] . ' is not exists');
        }

        if (!in_array($attributes['amount'], [$order->total_amount, $order->amount])) {
            abort(400, 'Amount ' . $attributes['amount'] . ' is not correct');
        }

        return OrderInvoice::create($attributes);
    }
}
