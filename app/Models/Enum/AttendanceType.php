<?php

namespace App\Models\Enum;

enum AttendanceType :int
{
    case IN = 0;
    case OUT = 1;
}
