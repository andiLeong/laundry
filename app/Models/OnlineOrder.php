<?php

namespace App\Models;

use App\Models\Enum\OnlineOrderStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineOrder extends Model
{
    use HasFactory;

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => OnlineOrderStatus::from($value)->toLower()
        );
    }
}
