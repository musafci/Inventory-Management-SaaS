<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    public function definition(): array
    {
        $slug = fake()->unique()->slug(2);

        return [
            'slug' => $slug,
            'name' => ucfirst(str_replace('-', ' ', $slug)),
            'price' => fake()->randomFloat(2, 0, 99),
            'limits' => [
                'max_warehouses' => 3,
                'max_users' => 5,
                'max_products' => 50,
                'max_orders_per_month' => 100,
                'api_rate_limit' => 120,
            ],
            'is_active' => true,
        ];
    }
}
