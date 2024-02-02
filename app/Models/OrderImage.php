<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

class OrderImage extends Model
{
    use HasFactory;

    public function creator()
    {
        return $this->belongsTo(User::class, 'uploaded_by', 'id');
    }

    public static function put(array $images, $userId, $orderId)
    {
        foreach ($images as $image) {
            /* @var $image UploadedFile */
            OrderImage::create([
                'order_id' => $orderId,
                'uploaded_by' => $userId,
                'path' => $image->storeAs('order', static::name($orderId, $image->extension()))
            ]);
        }
    }

    public static function name($orderId, $extension)
    {
        return $orderId . '_' . Str::random(32) . '.' . $extension;
    }
}
