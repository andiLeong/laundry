<?php

namespace App\Http\Controllers;

use App\Models\OrderInvoice;
use Illuminate\Http\Request;

class OrderInvoiceController extends Controller
{
    public function store(Request $request)
    {
        $attributes = $request->validate([
            'order_id' => 'required',
            'invoice_id' => 'required',
            'amount' => 'required|int',
        ]);

        return OrderInvoice::create($attributes);
    }
}
