<?php

namespace App\Listener;

use App\Event\OrderCreated;
use App\Models\Enum\OrderPayment;
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
                'payment' => OrderPayment::fromName($order->payment),
                'creator_id' => auth()->id(),
            ]);
        }
    }
}
