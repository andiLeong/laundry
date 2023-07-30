<?php

namespace App\Models\Promotions;

class NotQualifiedPromotion extends PromotionAbstract implements Promotion
{
    public function qualify(): bool
    {
        return false;
    }
}
