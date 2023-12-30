<?php

namespace App\Models\Enum;

enum WashMode :int
{
    case NORMAL = 1;
    case HOT = 2;
    case WARM = 3;
    case DELICATE = 4;
}
