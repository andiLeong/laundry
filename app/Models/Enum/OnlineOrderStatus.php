<?php

namespace App\Models\Enum;

enum OnlineOrderStatus: int
{
    case PENDING_PICKUP = 1;
    case PICKUP = 2;
    case DELIVERED = 3;

    public static function names()
    {
       return array_column(self::cases(), 'name');
    }

    public function toLower()
    {
        return strtolower($this->name);
    }
}
