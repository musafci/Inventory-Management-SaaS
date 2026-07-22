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
                'slug' => 'trial',
                'name' => 'Trial',
                'price' => 0,
                'limits' => [
                    'max_warehouses' => 1,
                    'max_users' => 3,
                    'max_products' => 25,
                    'max_orders_per_month' => 50,
                    'api_rate_limit' => 60,
                ],
            ],
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'price' => 29,
                'limits' => [
                    'max_warehouses' => 2,
                    'max_users' => 5,
                    'max_products' => 100,
                    'max_orders_per_month' => 200,
                    'api_rate_limit' => 120,
                ],
            ],
            [
                'slug' => 'pro',
                'name' => 'Pro',
                'price' => 79,
                'limits' => [
                    'max_warehouses' => 5,
                    'max_users' => 15,
                    'max_products' => 500,
                    'max_orders_per_month' => 1000,
                    'api_rate_limit' => 240,
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'price' => 199,
                'limits' => [
                    'max_warehouses' => null,
                    'max_users' => null,
                    'max_products' => null,
                    'max_orders_per_month' => null,
                    'api_rate_limit' => 600,
                ],
            ],
        ];

        foreach ($plans as $plan) {
            Plan::query()->updateOrCreate(
                ['slug' => $plan['slug']],
                [
                    'name' => $plan['name'],
                    'price' => $plan['price'],
                    'limits' => $plan['limits'],
                    'is_active' => true,
                ],
            );
        }

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
