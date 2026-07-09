<?php

namespace Database\Factories;

use App\Models\SalesFulfillment;
use App\Models\SalesFulfillmentItem;
use App\Models\SalesOrderItem;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesFulfillmentItem>
 */
class SalesFulfillmentItemFactory extends Factory
{
    protected $model = SalesFulfillmentItem::class;

    public function definition(): array
    {
        return [
            'sales_fulfillment_id' => SalesFulfillment::factory(),
            'sales_order_item_id' => SalesOrderItem::factory(),
            'quantity_fulfilled' => fake()->numberBetween(1, 5),
        ];
    }
}
