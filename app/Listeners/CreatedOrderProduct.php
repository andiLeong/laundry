<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Models\OrderPaid;
use App\Models\OrderProduct;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class CreatedOrderProduct
{
    /**
     * Create the event listener.
     */
    public function __construct(protected Request $request)
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(OrderCreated $event): void
    {
        $order = $event->order;
        $products = $event->products;

        if ($products instanceof Collection) {
            $products->each(fn($product) => OrderProduct::associate($order, $product));
        }

        if ($order->paid) {
            OrderPaid::create([
                'order_id' => $order->id,
                'amount' => $order->total_amount,
                'creator_id' => auth()->id(),
                'created_at' => now(),
            ]);
        }
    }
}
