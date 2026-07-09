<?php

namespace App\Models;

use Database\Factories\SalesOrderItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_order_id',
    'product_id',
    'quantity',
    'quantity_fulfilled',
    'quantity_returned',
    'unit_price',
    'discount',
    'subtotal',
])]
class SalesOrderItem extends Model
{
    /** @use HasFactory<SalesOrderItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'quantity_fulfilled' => 'integer',
            'quantity_returned' => 'integer',
            'unit_price' => 'decimal:2',
            'discount' => 'decimal:2',
            'subtotal' => 'decimal:2',
        ];
    }

    public function salesOrder(): BelongsTo
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function quantityRemainingToFulfill(): int
    {
        return max(0, $this->quantity - $this->quantity_fulfilled);
    }

    public function quantityRemainingToReturn(): int
    {
        return max(0, $this->quantity_fulfilled - $this->quantity_returned);
    }
}
