<?php

namespace App\Models\Promotions;

class RewardGiftCertificate extends PromotionAbstract implements Promotion
{

    public function qualify(): bool
    {
        $this->discount = $this->promotion->discount;
        return true;
    }
}
