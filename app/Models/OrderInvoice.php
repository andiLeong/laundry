<?php

namespace App\Models;

use App\QueryFilter\Filterable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderInvoice extends Model
{
    use HasFactory;
    use Filterable;
}
