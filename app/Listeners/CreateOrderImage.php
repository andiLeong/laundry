<?php

namespace App\Listeners;

use App\Event\OrderCreated;
use App\Models\OrderImage;
use Illuminate\Http\Request;
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
    public function handle(OrderCreated $event): void
    {
        $images = $this->request->file('image');
        if (is_null($images)) {
            return;
        }

        $order = $event->order;

        foreach ($images as $image) {
            $name = $order->id . '_' . Str::random(32) . '.' . $image->extension();
            $path = $image->storeAs('order', $name);
            OrderImage::create([
                'order_id' => $order->id,
                'uploaded_by' => $order->creator_id,
                'path' => $path
            ]);
        }
    }
}
