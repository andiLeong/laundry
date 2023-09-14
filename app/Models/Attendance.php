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
        'time' => 'datetime',
        'is_late' => 'boolean',
    ];

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => AttendanceType::from($value)->name
        );
    }

    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'staff_id', 'id');
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function outOfRange($lat, $log): bool
    {

        return false;
    }
}
