<?php

namespace Database\Seeders;

use App\Models\FeatureFlag;
use App\Models\Plan;
use Illuminate\Database\Seeder;

class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'price_monthly' => 29.00,
                'price_annual' => 288.00,
                'sort_order' => 1,
                'limits' => [
                    'max_warehouses' => 1,
                    'max_users' => 3,
                    'max_products' => 200,
                    'max_orders_per_month' => 300,
                    'api_rate_limit_per_minute' => null,
                ],
            ],
            [
                'slug' => 'growth',
                'name' => 'Growth',
                'price_monthly' => 79.00,
                'price_annual' => 780.00,
                'sort_order' => 2,
                'limits' => [
                    'max_warehouses' => 3,
                    'max_users' => 10,
                    'max_products' => 2000,
                    'max_orders_per_month' => 2000,
                    'api_rate_limit_per_minute' => 60,
                ],
            ],
            [
                'slug' => 'business',
                'name' => 'Business',
                'price_monthly' => 199.00,
                'price_annual' => 1980.00,
                'sort_order' => 3,
                'limits' => [
                    'max_warehouses' => 10,
                    'max_users' => 25,
                    'max_products' => null,
                    'max_orders_per_month' => 10000,
                    'api_rate_limit_per_minute' => 300,
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'price_monthly' => null,
                'price_annual' => null,
                'sort_order' => 4,
                'is_custom' => true,
                'limits' => [
                    'max_warehouses' => null,
                    'max_users' => null,
                    'max_products' => null,
                    'max_orders_per_month' => null,
                    'api_rate_limit_per_minute' => null,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'price_monthly' => $plan['price_monthly'],
                    'price_annual' => $plan['price_annual'],
                    'limits' => $plan['limits'],
                    'is_custom' => $plan['is_custom'] ?? false,
                    'grace_buffer_percent' => 10,
                    'sort_order' => $plan['sort_order'],
                    'is_active' => true,
                ],
            );
        }

        Plan::query()->whereNotIn('slug', collect($plans)->pluck('slug'))->delete();

        $flags = [
            ['key' => 'advanced_reporting', 'description' => 'Access to advanced reporting exports', 'default_enabled' => false],
            ['key' => 'multi_warehouse_transfers', 'description' => 'Stock transfers between warehouses', 'default_enabled' => true],
            ['key' => 'api_integrations', 'description' => 'Third-party API integrations', 'default_enabled' => false],
        ];

        foreach ($flags as $flag) {
            FeatureFlag::query()->updateOrCreate(
                ['key' => $flag['key']],
                [
                    'description' => $flag['description'],
                    'default_enabled' => $flag['default_enabled'],
                ],
            );
        }
    }
}
