<?php

namespace Database\Factories;

use App\Enums\SalesOrderStatus;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SalesOrder>
 */
class SalesOrderFactory extends Factory
{
    protected $model = SalesOrder::class;

    public function definition(): array
    {
        return [
            'customer_id' => Customer::factory(),
            'warehouse_id' => Warehouse::factory(),
            'order_number' => 'SO-'.fake()->unique()->numerify('######'),
            'status' => SalesOrderStatus::Draft,
            'order_date' => now()->toDateString(),
            'total_amount' => 0,
        ];
    }
}
