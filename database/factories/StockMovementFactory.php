<?php

namespace Database\Factories;

use App\Enums\StockMovementType;
use App\Models\Organization;
use App\Models\Product;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<StockMovement>
 */
class StockMovementFactory extends Factory
{
    protected $model = StockMovement::class;

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
            'type' => StockMovementType::AdjustmentIn,
            'quantity' => fake()->numberBetween(1, 50),
            'reference_type' => null,
            'reference_id' => null,
            'note' => null,
            'created_by' => User::factory(),
        ];
    }
}
