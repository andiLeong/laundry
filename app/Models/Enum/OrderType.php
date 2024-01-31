<?php

namespace App\Models\Enum;

enum OrderType :int
{
    case ONLINE = 1;
    case WALKIN = 2;

    public function toLower()
    {
        return strtolower($this->name);
    }
}
