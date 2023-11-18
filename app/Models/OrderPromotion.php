<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderPromotion extends Model
{
    use HasFactory;

    public static function insertByPromotions($promotions, Order $order)
    {
        foreach ($promotions as $promo) {
            OrderPromotion::insert([
                'order_id' => $order->id,
                'promotion_id' => $promo->id
            ]);
        }
    }
}
