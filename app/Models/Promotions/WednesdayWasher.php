<?php

namespace App\Models\Promotions;

use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;

class WednesdayWasher
{
    protected $discount;

    public function __construct(protected User $user, protected Service $service, protected Promotion $promotion)
    {
        //
    }


    /**
     * if user create order on tuesday we can give them discount
     * @return bool
     */
    public function qualify(): bool
    {

        $this->discount = 0.2;
        return true;
    }

    public function getDiscount()
    {
        return $this->discount;
    }

    public function __get(string $name)
    {
        return $this->promotion->{$name};
    }
}
