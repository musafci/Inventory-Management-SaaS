<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Organization;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

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
            'category_id' => Category::factory()->for($organization, 'organization'),
            'unit_id' => Unit::factory()->for($organization, 'organization'),
            'name' => fake()->words(3, true),
            'sku' => strtoupper(fake()->unique()->bothify('SKU-####')),
            'barcode' => fake()->unique()->ean13(),
            'cost_price' => fake()->randomFloat(2, 1, 500),
            'selling_price' => fake()->randomFloat(2, 5, 1000),
            'tax_rate' => fake()->randomFloat(2, 0, 20),
            'reorder_point' => fake()->optional()->numberBetween(1, 100),
            'is_active' => true,
        ];
    }
}
