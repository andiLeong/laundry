<?php

namespace App\Listeners;

use App\Events\OnlineOrderStatusUpdated;
use App\Events\OrderCreated;
use App\Events\OrderUpdated;
use App\Jobs\UploadOrderImages;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
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
        } elseif ($event instanceof OrderUpdated) {
            $orderId = $event->order->id;
            $creatorId = Auth::id();
        } else {
            $orderId = $event->onlineOrder->order_id;
            $creatorId = Auth::id();
        }

        $images = array_map(function ($image) {
            /* @var $image UploadedFile */
            return [
                'full_path' => config('filesystems.disks.local.root') . DIRECTORY_SEPARATOR . $image->store('order', ['disk' => 'local']),
                'extension' => $image->extension(),
            ];
        }, $images);

        UploadOrderImages::dispatch($images, $creatorId, $orderId);
    }
}
