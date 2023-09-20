<?php

namespace App\Models\Enum;

enum AttendanceType :int
{
    case in = 0;
    case out = 1;
}
