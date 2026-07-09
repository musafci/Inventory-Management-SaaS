<?php

namespace App\Models;

use App\Enums\StockMovementType;
use App\Traits\BelongsToOrganization;
use App\Traits\LogsModelActivity;
use Database\Factories\StockMovementFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use RuntimeException;

#[Fillable([
    'organization_id',
    'warehouse_id',
    'product_id',
    'type',
    'quantity',
    'reference_type',
    'reference_id',
    'note',
    'created_by',
])]
class StockMovement extends Model
{
    /** @use HasFactory<StockMovementFactory> */
    use BelongsToOrganization, HasFactory, LogsModelActivity;

    protected function casts(): array
    {
        return [
            'type' => StockMovementType::class,
            'quantity' => 'integer',
        ];
    }

    protected static function booted(): void
    {
        static::updating(fn () => throw new RuntimeException('stock_movements is append-only.'));
        static::deleting(fn () => throw new RuntimeException('stock_movements is append-only.'));
    }

    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reference(): MorphTo
    {
        return $this->morphTo();
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Signed delta applied to stocks.quantity_on_hand for this ledger entry.
     */
    public function signedQuantityDelta(): int
    {
        return $this->type->signedQuantityDelta($this->quantity);
    }
}
