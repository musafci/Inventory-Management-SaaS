<?php

namespace App\Models;

use Database\Factories\SalesFulfillmentItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'sales_fulfillment_id',
    'sales_order_item_id',
    'quantity_fulfilled',
])]
class SalesFulfillmentItem extends Model
{
    /** @use HasFactory<SalesFulfillmentItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity_fulfilled' => 'integer',
        ];
    }

    public function salesFulfillment(): BelongsTo
    {
        return $this->belongsTo(SalesFulfillment::class);
    }

    public function salesOrderItem(): BelongsTo
    {
        return $this->belongsTo(SalesOrderItem::class);
    }
}
