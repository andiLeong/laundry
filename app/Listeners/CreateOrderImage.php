<?php

namespace App\Listeners;

use App\Events\OnlineOrderStatusUpdated;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Models\OrderImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

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
    public function handle(OrderCreated|OnlineOrderStatusUpdated|OrderUpdated $event): void
    {
        $images = $this->request->file('image');
        if (is_null($images)) {
            return;
        }

        if ($event instanceof OrderCreated) {
            $orderId = $event->order->id;
            $creatorId = $event->order->creator_id;
        } else if ($event instanceof OrderUpdated) {
            $orderId = $event->order->id;
            $creatorId = Auth::id();
        } else {
            $orderId = $event->onlineOrder->order_id;
            $creatorId = Auth::id();
        }

        OrderImage::put($images, $creatorId, $orderId);
    }
}
