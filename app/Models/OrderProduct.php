<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OrderProduct extends Model
{
    use HasFactory;

    public $timestamps = false;

    public static function associate(Order $order, Product $product)
    {
        $quantity = $product->quantity;

        OrderProduct::create([
            'product_id' => $product['id'],
            'order_id' => $order->id,
            'quantity' => $quantity,
            'name' => $product->name,
            'price' => $product->price,
            'total_price' => $product->price * $quantity,
        ]);

        $product->decrement('stock', $quantity);
    }
}
