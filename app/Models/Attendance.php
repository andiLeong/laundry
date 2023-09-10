<?php

namespace App\Models;

use App\Models\Enum\AttendanceType;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Attendance extends Model
{
    use HasFactory;

    public $timestamps = false;
    protected $casts = [
        'time' => 'datetime'
    ];

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => AttendanceType::from($value)->name
        );
    }
}
