<?php

namespace App\Models\Enum;

enum OrderPayment :int
{
    case cash = 1;
    case gcash = 2;
}
