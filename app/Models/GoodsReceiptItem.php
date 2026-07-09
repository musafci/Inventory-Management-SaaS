<?php

namespace App\Models;

use Database\Factories\GoodsReceiptItemFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'goods_receipt_id',
    'purchase_order_item_id',
    'quantity_received',
])]
class GoodsReceiptItem extends Model
{
    /** @use HasFactory<GoodsReceiptItemFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'quantity_received' => 'integer',
        ];
    }

    public function goodsReceipt(): BelongsTo
    {
        return $this->belongsTo(GoodsReceipt::class);
    }

    public function purchaseOrderItem(): BelongsTo
    {
        return $this->belongsTo(PurchaseOrderItem::class);
    }
}
