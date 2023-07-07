<?php

namespace App\Models;

use App\QueryFilter\Filterable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable, Filterable;

    protected $hidden = ['password'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'phone_verified_at' => 'datetime'
    ];

    protected static function booted(): void
    {
        static::addGlobalScope('verified', function (Builder $builder) {
            $builder->whereNotNull('phone_verified_at');
        });
    }

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
       return $this->hasMany(Order::class,'user_id','id');
    }

    public function verification()
    {
        return $this->hasOne(VerificationToken::class,'user_id','id')->orderByDesc('id');
    }

    protected function type(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => UserType::from($value)->name
        );
    }

    protected function password(): Attribute
    {
        return Attribute::make(
            get: null,
            set: fn (string $value) => bcrypt($value),
        );
    }

    public function isCustomer()
    {
        return $this->type === UserType::customer->name;
    }

    public function isEmployee()
    {
        return $this->type === UserType::employee->name;
    }

    public function isAdmin()
    {
        return $this->type === UserType::admin->name;
    }

    public function isVerified()
    {
        return !is_null($this->phone_verified_at);
    }

    public static function findUnverifiedById($id)
    {
        return User::withoutGlobalScope('verified')->find($id);
    }
}
