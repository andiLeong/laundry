<?php

namespace App\Models;

use App\Models\Enum\AttendanceType;
use Exception;
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

    const PASSING_RANGE = 200;

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => AttendanceType::from($value)->name
        );
    }

    public function staff(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Staff::class, 'staff_id', 'id');
    }

    public function branch(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public static function outOfRange($lat, $long, $branchId): bool
    {
        try {
            $distance = Branch::query()
                ->selectRaw('ST_Distance(
                   ST_SRID(Point(longitude, latitude), 4326),
                   ST_SRID(Point(?, ?), 4326)
                ) as distance', [$long, $lat]
                )
                ->where('id', $branchId)
                ->first()['distance'];

            return $distance >= static::PASSING_RANGE;
        } catch (Exception) {
            return false;
        }
    }
}
