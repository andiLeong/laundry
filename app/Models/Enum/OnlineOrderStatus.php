<?php

namespace App\Models\Enum;

enum OnlineOrderStatus :int
{
    case PENDING_PICKUP = 1;
    case PICKUP = 2;
    case DELIVERED = 3;
}
