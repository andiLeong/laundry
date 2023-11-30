<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPaid extends Model
{
    use HasFactory;
    use DatetimeQueryScope;

    public $timestamps = false;
    protected $table = 'order_paid';

    public function creator()
    {
       return $this->belongsTo(User::class,'creator_id','id');
    }

    public function order()
    {
       return $this->belongsTo(Order::class,'order_id','id');
    }
}
