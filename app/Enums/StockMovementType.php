<?php

namespace App\Enums;

enum StockMovementType: string
{
    case PurchaseIn = 'purchase_in';
    case SaleOut = 'sale_out';
    case AdjustmentIn = 'adjustment_in';
    case AdjustmentOut = 'adjustment_out';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case ReturnIn = 'return_in';
    case ReturnOut = 'return_out';

    public function isInbound(): bool
    {
        return match ($this) {
            self::PurchaseIn,
            self::AdjustmentIn,
            self::TransferIn,
            self::ReturnIn => true,
            default => false,
        };
    }

    public function signedQuantityDelta(int $quantity): int
    {
        return $this->isInbound() ? $quantity : -$quantity;
    }

    /**
     * Movement types allowed via the manual stock adjustment API.
     *
     * @return list<self>
     */
    public static function manualAdjustmentTypes(): array
    {
        return [
            self::AdjustmentIn,
            self::AdjustmentOut,
        ];
    }
}
