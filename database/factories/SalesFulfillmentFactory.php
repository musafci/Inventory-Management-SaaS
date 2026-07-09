<?php

namespace Database\Factories;

use App\Models\SalesFulfillment;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesFulfillment>
 */
class SalesFulfillmentFactory extends Factory
{
    protected $model = SalesFulfillment::class;

    public function definition(): array
    {
        return [
            'sales_order_id' => SalesOrder::factory(),
            'fulfilled_by' => User::factory(),
            'note' => fake()->optional()->sentence(),
            'fulfilled_at' => now(),
        ];
    }
}
