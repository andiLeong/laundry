<?php

namespace App\Models\Enum;

enum OrderType :int
{
    case ONLINE = 1;
    case WALKIN = 2;
}
