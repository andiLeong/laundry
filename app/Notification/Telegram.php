<?php

namespace App\Notification;

use App\Models\Order;
use Illuminate\Support\Facades\Http;

class Telegram
{
    protected ?string $baseUrl;

    /**
     * list of tg chat ids
     * @var string[]
     */
    private array $chatIds;

    /**
     * the target url to fire
     * @var string
     */
    private string $url;

    public function __construct(private string $token)
    {
        $this->baseUrl = config()->get('services.telegram.base_url');
        $this->chatIds = explode(',', config()->get('services.telegram.chat_ids'));

        $endPoint = 'bot' . $this->token . '/sendMessage';
        $this->url = $this->baseUrl . $endPoint;
    }

    public function sendOrderCreatedNotification(Order $order): bool
    {
        $message = "service: {$order->service->name}, \n total amount: {$order->total_amount},\n service amount: {$order->amount},\n product amount: {$order->product_amount},\n order description: {$order->description}\n ";
        return $this->send($message);
    }

    public function sentShopCloseReminder($name)
    {
        $message = "Dear {$name},  please remember to:

    1, Clear the machine,
    2, Make OR if needed (using real system order data) inclusive update the system,
    3, Count the money
    4, Turn off everything

Thanks.";
        return $this->send($message);
    }

    protected function send($message)
    {
        foreach ($this->chatIds as $id) {
            logger('sending telegram notification');
            $response = Http::get($this->url, [
                'chat_id' => $id,
                'text' => $message
            ]);

            if ($response->successful() === false) {
                logger($response->body());
                return false;
            }
        }

        return true;
    }

}
