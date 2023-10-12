<?php

namespace App\Http\Controllers;

use App\Models\Enum\OrderPayment;
use App\Models\GcashOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GcashOrderController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => ['required', Rule::exists('orders', 'id')->where('payment', OrderPayment::gcash->value)],
            'reference_number' => 'required|string',
        ]);

        return GcashOrder::create($validated);
    }
}
