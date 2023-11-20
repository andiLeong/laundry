<?php

namespace App\Models;

use App\Models\Enum\OrderPayment;
use App\QueryFilter\Filterable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Order extends Model
{
    use HasFactory;
    use Filterable;
    use DatetimeQueryScope;

    protected $casts = [
        'paid' => 'boolean',
        'confirmed' => 'boolean',
        'issued_invoice' => 'boolean'
    ];

    protected function payment(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => OrderPayment::from($value)->name
        );
    }

    public function user(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function creator(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(User::class, 'creator_id');
    }

    public function service(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function promotions(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Promotion::class, 'order_promotions', 'order_id', 'promotion_id');
    }

    public function products()
    {
        return $this->hasMany(OrderProduct::class, 'order_id', 'id');
    }

    public function gcash()
    {
        return $this->hasOne(GcashOrder::class);
    }

    public function paid()
    {
        $this->update(['paid' => true]);
    }

    public function unpaid()
    {
        $this->update(['paid' => false]);
    }

    public function issueInvoice()
    {
        $this->update(['issued_invoice' => true]);
    }

    public function unissueInvoice()
    {
        $this->update(['issued_invoice' => false]);
    }
}
