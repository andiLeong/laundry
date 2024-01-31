<?php

namespace App\Listeners;

use App\Event\OrderCreated;
use App\Events\OnlineOrderStatusUpdated;
use App\Models\OrderImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
    public function handle(OrderCreated|OnlineOrderStatusUpdated $event): void
    {
        $images = $this->request->file('image');
        if (is_null($images)) {
            return;
        }

        if($event instanceof OrderCreated){
            $orderId = $event->order->id;
            $creatorId = $event->order->creator_id;
        }else{
            $orderId = $event->onlineOrder->order_id;
            $creatorId = Auth::id();
        }

        foreach ($images as $image) {
            $name = $orderId . '_' . Str::random(32) . '.' . $image->extension();
            OrderImage::create([
                'order_id' => $orderId,
                'uploaded_by' => $creatorId,
                'path' => $image->storeAs('order', $name)
            ]);
        }
    }
}
