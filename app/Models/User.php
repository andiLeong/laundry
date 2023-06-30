<?php

namespace App\Models;

use Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $hidden = ['password'];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'phone_verified_at' => 'datetime'
    ];

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
       return $this->hasMany(Order::class,'user_id','id');
    }

    public function verification()
    {
        return $this->hasOne(VerificationToken::class,'user_id','id')->orderByDesc('id');
    }

    public function getTypeAttribute($value)
    {
        return UserType::from($value)->name;
    }

    public function isCustomer()
    {
        return $this->type === UserType::customer->name;
    }

    public function isVerified()
    {
        return !is_null($this->phone_verified_at);
    }
}
