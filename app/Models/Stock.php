<?php

namespace App\Models;

use App\Traits\BelongsToOrganization;
use Database\Factories\StockFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use RuntimeException;

#[Fillable([
    'organization_id',
    'warehouse_id',
    'product_id',
    'quantity_on_hand',
    'quantity_reserved',
    'last_counted_at',
])]
class Stock extends Model
{
    /** @use HasFactory<StockFactory> */
    use BelongsToOrganization, HasFactory;

    /**
     * Set only by StockMovementObserver while applying a ledger entry.
     * Do not toggle outside the observer.
     */
    public static bool $quantityOnHandUpdateFromMovement = false;

    /**
     * Set only by StockService while reserving or releasing sales-order stock.
     */
    public static bool $quantityReservedUpdateFromService = false;

    protected function casts(): array
    {
        return [
            'quantity_on_hand' => 'integer',
            'quantity_reserved' => 'integer',
            'last_counted_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Stock $stock): void {
            if (
                $stock->exists
                && $stock->isDirty('quantity_on_hand')
                && ! static::$quantityOnHandUpdateFromMovement
            ) {
                throw new RuntimeException(
                    'stocks.quantity_on_hand must NEVER be updated directly. '.
                    'Insert a stock_movements row via StockService::recordMovement() instead.',
                );
            }

            if (
                $stock->exists
                && $stock->isDirty('quantity_reserved')
                && ! static::$quantityReservedUpdateFromService
            ) {
                throw new RuntimeException(
                    'stocks.quantity_reserved must NEVER be updated directly. '.
                    'Use StockService::reserveQuantity() or releaseReservation() instead.',
                );
            }
        });
    }

    protected function quantityAvailable(): Attribute
    {
        return Attribute::get(
            fn (): int => $this->quantity_on_hand - $this->quantity_reserved,
        );
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
