<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Product;
use App\Models\Stock;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Stock>
 */
class StockFactory extends Factory
{
    protected $model = Stock::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $organization = Organization::factory();

        return [
            'organization_id' => $organization,
            'warehouse_id' => Warehouse::factory()->for($organization, 'organization'),
            'product_id' => Product::factory()->for($organization, 'organization'),
            'quantity_on_hand' => 0,
            'quantity_reserved' => 0,
            'last_counted_at' => null,
        ];
    }
}
