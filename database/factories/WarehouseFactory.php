<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\Warehouse;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Warehouse>
 */
class WarehouseFactory extends Factory
{
    protected $model = Warehouse::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'organization_id' => Organization::factory(),
            'name' => fake()->unique()->words(2, true).' Warehouse',
            'address' => fake()->streetAddress(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(['is_default' => true]);
    }
}
