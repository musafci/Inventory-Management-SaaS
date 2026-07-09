<?php

namespace App\Enums;

enum SalesOrderStatus: string
{
    case Draft = 'draft';
    case Confirmed = 'confirmed';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Draft, self::Confirmed], true);
    }

    public function isConfirmable(): bool
    {
        return $this === self::Draft;
    }

    public function hasActiveReservation(): bool
    {
        return $this === self::Confirmed;
    }

    public function isFulfillable(): bool
    {
        return $this === self::Confirmed;
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::Confirmed, self::Shipped, self::Delivered], true);
    }

    public function isDeliverable(): bool
    {
        return $this === self::Shipped;
    }

    public function isRefundable(): bool
    {
        return in_array($this, [self::Shipped, self::Delivered], true);
    }
}
