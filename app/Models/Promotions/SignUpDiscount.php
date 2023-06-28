<?php

namespace App\Models\Promotions;

use App\Models\Order;

class SignUpDiscount extends PromotionAbstract implements Promotion
{
    public function qualify(): bool
    {
        if (!$this->isFullService() || Order::where('user_id', $this->user->id)->count() > 0) {
            return false;
        }

        $this->discount = 0.5;
        return true;
    }
}
