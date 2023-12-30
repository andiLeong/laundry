<?php

namespace App\Models\Enum;

enum LaundryType :int
{
    case MIXED_CLOTHES = 1;
    case BLANKET_BEDSHEET_COMFORTER = 2;
    case TOWELS = 3;
}
