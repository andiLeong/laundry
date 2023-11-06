<?php

namespace App\Models\Enum;

enum WashMode :int
{
    case normal = 1;
    case hot = 2;
    case warm = 3;
    case delicate = 4;
}
