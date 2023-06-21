<?php

namespace App\Models;

enum UserType :int
{
    case customer = 0;
    case admin = 1;
    case employee = 2;
}
