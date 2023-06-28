<?php

namespace App\Models\Promotions;

use App\Models\Order;

class SignUpDiscount extends PromotionAbstract implements Promotion
{
    public function qualify(): bool
    {
        if (!$this->service->isFull()) {
            return false;
        }

        if (Order::where('user_id', $this->user->id)->count() > 0) {
            return false;
        }

        $this->discount = 0.3;
        return true;
    }
}
