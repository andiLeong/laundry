<?php

namespace App\Http\Controllers;

use App\Models\Invoice;
use App\Models\Order;
use App\Models\OrderInvoice;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InvoiceController extends Controller
{
    public function index(Request $request)
    {
        return Invoice::query()
            ->filters([
                'invoice_id' => [
                    'clause' => 'whereIn',
                    'value' => explode(',', $request->get('invoice_id')),
                ],
            ], $request)
            ->orderBy('id', 'desc')
            ->paginate();
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'order_id' => 'required|string',
            'invoice_id' => 'required|unique:invoices',
            'amount' => 'required|int',
            'name' => 'required|string|max:255',
        ]);
        $orderIds = explode(',', $attributes['order_id']);
        $orders = Order::whereIn('id', $orderIds)->get();
        $orderInvoice = OrderInvoice::whereIn('order_id', $orderIds)->get();
        if ($orders->isEmpty() || count($orderIds) != $orders->count() || $orderInvoice->isNotEmpty()) {
            abort(400, 'Order Ids are not correct');
        }

        return DB::transaction(function () use ($attributes, $orderIds) {
            unset($attributes['order_id']);
            $invoice = Invoice::create($attributes);
            foreach ($orderIds as $orderId) {
                OrderInvoice::create([
                    'order_id' => $orderId,
                    'invoice_id' => $invoice->id,
                ]);
            }

            return $invoice;
        });
    }
}
