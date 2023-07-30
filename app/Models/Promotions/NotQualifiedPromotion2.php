<?php

namespace App\Models\Promotions;

class NotQualifiedPromotion2 extends PromotionAbstract implements Promotion
{
    public function qualify(): bool
    {
        return false;
    }
}
