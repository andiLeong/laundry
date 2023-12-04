<?php

namespace App\Listeners;

use App\Models\Order;
use App\Notification\Telegram;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;

class SendNotification
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(object $event): void
    {
        $order = $event->order;
        $telegram = resolve(Telegram::class);
        $telegram->sendOrderCreatedNotification($order);
    }
}
