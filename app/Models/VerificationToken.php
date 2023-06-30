<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\App;

class VerificationToken extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'expired_at' => 'datetime'
    ];

    public static function generate()
    {
        return !App::runningUnitTests()
            ? rand(10000, 99999)
            : 8899;
    }
}
