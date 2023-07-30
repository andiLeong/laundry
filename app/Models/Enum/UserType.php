<?php

namespace App\Models\Enum;

enum UserType :int
{
    case customer = 0;
    case admin = 1;
    case employee = 2;
}
