<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductOrder extends Model
{
    use HasFactory;

    public $timestamps = false;

    public static function associate(Order $order, array $product)
    {
        $quantity = array_key_exists('quantity', $product)
            ? $product['quantity']
            : 1;

        ProductOrder::create([
            'product_id' => $product['id'],
            'order_id' => $order->id,
            'quantity' => $quantity
        ]);
        Product::where('id',$product['id'])->decrement('stock', $quantity);
    }
}
