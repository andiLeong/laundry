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
        return $this->id === 1 || str_contains($this->name, 'full');
    }
}
