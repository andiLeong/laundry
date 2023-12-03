<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shift extends Model
{
    use HasFactory;
    use DatetimeQueryScope;

    public $timestamps = false;

    protected $casts = [
        'from' => 'datetime',
        'to' => 'datetime',
        'date' => 'date:Y-m-d',
        'late' => 'boolean',
        'absence' => 'boolean',
        'early_leave' => 'boolean',
        'reviewed' => 'boolean',
    ];

    const TIME_SPAN = 3;

    public function scopeUnreviewed($query)
    {
        return $query->where('reviewed', false);
    }

    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'shift_id', 'id');
    }
}
