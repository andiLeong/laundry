<?php

namespace App\Notification;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class Telegram
{
    protected ?string $baseUrl;

    public function __construct(private string $token)
    {
        $this->baseUrl = config()->get('services.telegram.base_url');
    }

    public function sendOrderCreatedNotification(Order $order): bool
    {
        $chatIds = explode(',', config()->get('services.telegram.chat_ids'));
        $endPoint = 'bot' . $this->token . '/sendMessage';
        $url = $this->baseUrl . $endPoint;

        $message = "service: {$order->service->name}, \n total amount: {$order->total_amount},\n service amount: {$order->amount},\n product amount: {$order->product_amount},\n order description: {$order->description}\n ";

        foreach ($chatIds as $id) {
            logger('sending telegram notification');
            $response = Http::get($url, [
                'chat_id' => $id,
                'text' => $message
            ]);

            if($response->successful() === false){
                logger($response->body());
                return false;
            }
        }

        return true;
    }


}
