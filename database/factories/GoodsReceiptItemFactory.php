<?php

namespace Database\Factories;

use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<GoodsReceiptItem>
 */
class GoodsReceiptItemFactory extends Factory
{
    protected $model = GoodsReceiptItem::class;

    public function definition(): array
    {
        return [
            'goods_receipt_id' => GoodsReceipt::factory(),
            'purchase_order_item_id' => PurchaseOrderItem::factory(),
            'quantity_received' => fake()->numberBetween(1, 10),
        ];
    }
}
