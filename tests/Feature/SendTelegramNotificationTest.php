<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\OrderCanBeCreated;
use Tests\TestCase;

class SendTelegramNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;
    use OrderCanBeCreated;

    /** @test */
    public function it_can_send_telegram_notification_to_group_chat(): void
    {
        $this->assertDatabaseCount('orders', 0);
        $response = $this->createOrderWithMock();
        $this->assertDatabaseCount('orders', 1);
    }
}
