<?php

namespace App\Models;

use App\Models\Enum\OnlineOrderStatus;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class OnlineOrder extends Model
{
    use HasFactory;

    protected function status(): Attribute
    {
        return Attribute::make(
            get: fn(string $value) => OnlineOrderStatus::from($value)->toLower()
        );
    }

    public function isDelivied()
    {
        return $this->status === OnlineOrderStatus::DELIVERED->toLower();
    }

    public function isPickup()
    {
        return $this->status == OnlineOrderStatus::PICKUP->toLower();
    }

    public function isNotPickup()
    {
       return !$this->isPickup();
    }

    public function isPending()
    {
        return $this->status == OnlineOrderStatus::PENDING_PICKUP->toLower();
    }

    public function isNotPending()
    {
       return !$this->isPending();
    }

    public function markAsPickup()
    {
        if($this->isNotPending()){
            throw new LogicException('Order must from pending pickup to picked up');
        }

        $this->update([
            'pickup_at' => now(),
            'status' => OnlineOrderStatus::PICKUP->value,
        ]);
    }

    public function markAsDelivered()
    {
        if($this->isNotPickup()){
            throw new LogicException('Order need to pickup first, then set delivery status');
        }

        $this->update([
            'deliver_at' => now(),
            'status' => OnlineOrderStatus::DELIVERED->value,
        ]);
    }
}
