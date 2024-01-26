<?php

namespace App\Listeners;

use App\Event\OrderCreated;
use App\Models\OrderImage;
use Illuminate\Http\Request;

class CreateOrderImage
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
        if(is_null($this->request->hasFile('images'))){
            return;
        }

        $order = $event->order;

        $path = $this->request->file('images')->store('order');
        OrderImage::create([
            'order_id' => $order->id,
            'uploaded_by' => $order->creator_id,
            'path' => $path
        ]);
    }
}
