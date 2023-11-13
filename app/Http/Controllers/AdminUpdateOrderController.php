<?php

namespace App\Http\Controllers;

use App\Models\Order;
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
            return;
        }

        $this->order->paid();
    }
}
