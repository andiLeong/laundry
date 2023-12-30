<?php

namespace App\Models\Enum;

enum UserType :int
{
    case CUSTOMER = 0;
    case ADMIN = 1;
    case EMPLOYEE = 2;

    public function toLower()
    {
       return strtolower($this->name);
    }
}
