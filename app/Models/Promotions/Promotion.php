<?php

namespace App\Models\Promotions;

interface Promotion
{
    public function qualify() :bool ;
    public function getDiscount();
}
