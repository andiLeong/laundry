<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VerificationToken extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'expired_at' => 'datetime'
    ];
}
