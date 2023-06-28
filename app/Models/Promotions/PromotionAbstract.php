<?php

namespace App\Models\Promotions;

use App\Models\Promotion;
use App\Models\Service;
use App\Models\User;
use Illuminate\Contracts\Support\Jsonable;

abstract class PromotionAbstract implements Jsonable
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

    public function getDiscount()
    {
        return $this->discount;
    }

    protected function isFullService() :bool
    {
        return $this->service->isFull();
    }

    public function toJson($options = 0)
    {
       return $this->promotion->toJson($options);
    }

    public function __get(string $name)
    {
        return $this->promotion->{$name};
    }

    public function __call(string $name, array $arguments)
    {
        return $this->promotion->$name(...$arguments);
    }

}
