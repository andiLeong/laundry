<?php

namespace App\Models\Promotions;

use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;

class SignUpDiscount
{
    protected $discount;

    public function __construct(protected User $user, protected Service $service, protected Promotion $promotion)
    {
        //
    }

    //if user is qualified for the promotion we can apply discount for him
    public function qualify(): bool
    {

        $this->discount = 0.3;
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
