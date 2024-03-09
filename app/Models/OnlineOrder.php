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

    public function isDelivered()
    {
        return $this->status === OnlineOrderStatus::DELIVERED->toLower();
    }

    /**
     * determine if intended update status is coming from previous status
     * @param $status
     * @return bool
     */
    protected function cantUpdateStatus($status): bool
    {
        $names = OnlineOrderStatus::names();
        $statusKey = array_search(strtoupper($this->status), $names);
        $targetKey = array_search($status, $names);
        if ($statusKey === false || $targetKey === false) {
            return false;
        }

        return ($statusKey + 1) !== $targetKey;
    }

    public function markAsPickup()
    {
        if ($this->cantUpdateStatus(OnlineOrderStatus::PICKUP->name)) {
            throw new LogicException('Order must from pending pickup to picked up');
        }

        $this->update([
            'pickup_at' => now(),
            'status' => OnlineOrderStatus::PICKUP->value,
        ]);
    }

    public function markAsDelivered()
    {
        if ($this->cantUpdateStatus(OnlineOrderStatus::DELIVERED->name)) {
            throw new LogicException('Order need to pickup first, then set delivery status');
        }

        $this->update([
            'deliver_at' => now(),
            'status' => OnlineOrderStatus::DELIVERED->value,
        ]);
    }
}
