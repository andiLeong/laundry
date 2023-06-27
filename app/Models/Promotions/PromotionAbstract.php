<?php

namespace App\Models\Promotions;

use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;

abstract class PromotionAbstract
{
    protected $discount;

    public function __construct(
        protected User $user,
        protected Service $service,
        protected Promotion $promotion
    )
    {
        //
    }

    public function __get(string $name)
    {
        return $this->promotion->{$name};
    }

    public function getDiscount()
    {
        return $this->discount;
    }
}
