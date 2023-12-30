<?php

namespace App\Http\Controllers;

use App\Models\Enum\OrderPayment;
use App\Models\Order;
use App\Models\OrderPaid;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminUpdateOrderController extends Controller
{
    private $order;

    public function __construct(protected Request $request)
    {
        //
    }

    public function update($id, $column)
    {
        $order = Order::find($id);

        if (is_null($order)) {
            abort(404, 'Order is not existed');
        }

        $this->order = $order;
        $column = Str::camel($column);
        if (!method_exists($this, $column)) {
            abort(422, 'We can\'t handle your request, please try again later');
        }

        return $this->$column();
    }

    public function issuedInvoice()
    {
        if ($this->order->issued_invoice) {
            $this->order->unissueInvoice();
            return;
        }

        $this->order->issueInvoice();
    }

    public function paid()
    {
        if ($this->order->paid) {
            $this->order->unpaid();
            OrderPaid::where('order_id', $this->order->id)->delete();
            return;
        }

        $this->order->paid();
        OrderPaid::create([
            'order_id' => $this->order->id,
            'amount' => $this->order->total_amount,
            'creator_id' => auth()->id(),
            'created_at' => now(),
        ]);
    }

    public function payment()
    {
        if ($this->order->payment === OrderPayment::CASH->name) {
            $this->order->update(['payment' => OrderPayment::GCASH->value]);
            return;
        }

        $this->order->update(['payment' => OrderPayment::CASH->value]);
    }
}
