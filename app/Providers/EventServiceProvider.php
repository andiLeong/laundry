<?php

namespace App\Providers;

use App\Events\OrderUpdated;
use App\Events\OnlineOrderStatusUpdated;
use App\Events\OrderCreated;
use App\Listeners\CreatedOrderProduct;
use App\Listeners\CreateOrderImage;
use App\Listeners\SendNotification;
use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;

class EventServiceProvider extends ServiceProvider
{
    /**
     * The event to listener mappings for the application.
     *
     * @var array<class-string, array<int, class-string>>
     */
    protected $listen = [
        OrderCreated::class => [
            CreatedOrderProduct::class,
            SendNotification::class,
            CreateOrderImage::class,
        ],
        OnlineOrderStatusUpdated::class => [
//            CreateOrderImage::class,
        ],
        OrderUpdated::class => [
            CreateOrderImage::class,
        ],
    ];

    /**
     * Register any events for your application.
     */
    public function boot(): void
    {
        //
    }

    /**
     * Determine if events and listeners should be automatically discovered.
     */
    public function shouldDiscoverEvents(): bool
    {
        return false;
    }
}
