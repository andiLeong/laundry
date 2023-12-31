<?php

namespace App\Models\Promotions;

class WednesdayWasher extends PromotionAbstract implements Promotion
{
    /**
     * if user create order on tuesday we can give them discount
     * @return bool
     */
    public function qualify(): bool
    {

        $this->discount = $this->promotion->discount;
        return true;
    }
}
