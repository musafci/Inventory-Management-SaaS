<?php

namespace Database\Factories;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PurchaseOrder>
 */
class PurchaseOrderFactory extends Factory
{
    protected $model = PurchaseOrder::class;

    public function definition(): array
    {
        return [
            'supplier_id' => Supplier::factory(),
            'warehouse_id' => Warehouse::factory(),
            'po_number' => 'PO-'.fake()->unique()->numerify('######'),
            'status' => PurchaseOrderStatus::Draft,
            'order_date' => now()->toDateString(),
            'expected_date' => now()->addDays(7)->toDateString(),
            'total_amount' => 0,
        ];
    }
}
