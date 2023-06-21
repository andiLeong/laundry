<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;


    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [];

    public function orders(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
       return $this->hasMany(Order::class,'user_id','id');
    }

    public function getTypeAttribute($value)
    {
        return UserType::from($value)->name;
    }

    public function isCustomer()
    {
        return $this->type === UserType::customer->name;
    }
}
