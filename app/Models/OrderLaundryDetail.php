<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderLaundryDetail extends Model
{
    use HasFactory;

    protected $casts = [
        'extra_wash' => 'boolean',
        'extra_rinse' => 'boolean',
    ];
}
