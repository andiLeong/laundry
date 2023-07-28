<?php

namespace App\Listener;

use App\Event\OrderCreated;
use App\Models\ProductOrder;
use Illuminate\Http\Request;

class CreatedProductOrder
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

        if ($this->request->has('product_ids')) {
            collect($this->request->get('product_ids'))
                ->each(fn($product) => ProductOrder::associate($order, $product));
        }
    }
}
