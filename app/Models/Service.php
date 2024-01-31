<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Service extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $casts = [
        'full_service' => 'boolean',
        'primary' => 'boolean',
    ];

    public function applyDiscount($discount)
    {
        return $this->price - $this->price * $discount;
    }

    public function isFull()
    {
        return $this->full_service;
    }

    public static function mostRequested()
    {
        return static::where('up_to', 8)
            ->where('full_service', true)
            ->where('primary', true)
            ->first();
    }
}
