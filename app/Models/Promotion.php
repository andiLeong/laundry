<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\QueryFilter\Filterable;

class Promotion extends Model
{
    use HasFactory;
    use Filterable;

    protected $hidden = ['class'];

    protected $casts = [
        'status' => 'boolean',
        'isolated' => 'boolean',
        'start' => 'datetime',
        'until' => 'datetime',
    ];

    public function forever(): bool
    {
        return is_null($this->until);
    }

    public function expired(): bool
    {
        if ($this->forever()) {
            return false;
        }

        return now()->gt($this->until);
    }

    protected function discount(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => floatval($value),
            set: null,
        );
    }

    public function isIsolated()
    {
       return $this->isolated;
    }

    public function active()
    {
       return $this->status == true;
    }

    public function scopeEnabled(Builder $query)
    {
        $query->where('status', 1);
    }

    public function scopeAvailable(Builder $query)
    {
        $now = now();
        $query
            ->where('start', '<', $now)
            ->where(fn($query) =>
                $query->whereNull('until')->orWhere('until', '>', $now)
            );
    }
}
