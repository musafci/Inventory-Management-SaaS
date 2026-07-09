<?php

namespace App\Enums;

enum PurchaseOrderStatus: string
{
    case Draft = 'draft';
    case Sent = 'sent';
    case PartiallyReceived = 'partially_received';
    case Received = 'received';
    case Cancelled = 'cancelled';

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isReceivable(): bool
    {
        return in_array($this, [self::Sent, self::PartiallyReceived], true);
    }

    public function isCancellable(): bool
    {
        return in_array($this, [self::Draft, self::Sent], true);
    }

    public function isPayable(): bool
    {
        return in_array($this, [self::PartiallyReceived, self::Received], true);
    }
}
