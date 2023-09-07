<?php

namespace App\Listener;

use App\Event\OrderCreated;
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
//            if ($this->request->has('product_ids')) {
//            collect($this->request->get('product_ids'))
                $products->each(fn($product) => OrderProduct::associate($order, $product));
        }
    }
}
