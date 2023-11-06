<?php

namespace App\Models\Enum;

enum LaundryType :int
{
    case mixed_clothes = 1;
    case blanket_bedsheet_comforter = 2;
    case towels = 3;
}
