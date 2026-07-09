<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrderItem>
 */
class SalesOrderItemFactory extends Factory
{
    protected $model = SalesOrderItem::class;

    public function definition(): array
    {
        $quantity = fake()->numberBetween(1, 10);
        $unitPrice = fake()->randomFloat(2, 5, 50);
        $discount = 0;

        return [
            'sales_order_id' => SalesOrder::factory(),
            'product_id' => Product::factory(),
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'discount' => $discount,
            'subtotal' => round(($quantity * $unitPrice) - $discount, 2),
        ];
    }
}
