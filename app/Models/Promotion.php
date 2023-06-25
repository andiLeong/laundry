<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Promotion extends Model
{
    use HasFactory;

    protected $casts = [
        'status' => 'boolean',
        'isolated' => 'boolean',
        'start' => 'datetime',
        'until' => 'datetime',
    ];

    public function forever() :bool
    {
       return is_null($this->until);
    }

    public function expired() :bool
    {
        if($this->forever()){
            return false;
        }

        return now()->gt($this->until);
    }
}
