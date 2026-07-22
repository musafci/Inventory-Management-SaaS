<?php

namespace Database\Seeders;

use App\Enums\OrganizationStatus;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\OrganizationSubscriptionService;
use App\Services\StockService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $this->call(PlanSeeder::class);

        PlatformAdmin::query()->firstOrCreate(
            ['email' => 'platform@demo.test'],
            [
                'name' => 'Platform Admin',
                'password' => Hash::make('password123'),
            ],
        );

        $acme = $this->seedOrganization(
            name: 'Acme Warehouse',
            slug: 'acme-warehouse',
            ownerEmail: 'owner@acme.demo',
            ownerName: 'Alice Owner',
        );

        $beta = $this->seedOrganization(
            name: 'Beta Retail',
            slug: 'beta-retail',
            ownerEmail: 'owner@beta.demo',
            ownerName: 'Bob Owner',
        );

        $consultant = User::query()->firstOrCreate(
            ['email' => 'consultant@demo.test'],
            [
                'name' => 'Carol Consultant',
                'password' => Hash::make('password123'),
                'default_organization_id' => $acme->id,
            ],
        );

        $consultant->organizations()->syncWithoutDetaching([
            $acme->id => ['role' => 'Admin'],
            $beta->id => ['role' => 'Manager'],
        ]);

        setPermissionsTeamId($acme->id);
        $consultant->syncRoles(['Admin']);

        setPermissionsTeamId($beta->id);
        $consultant->assignRole('Manager');
    }

    protected function seedOrganization(
        string $name,
        string $slug,
        string $ownerEmail,
        string $ownerName,
    ): Organization {
        $organization = Organization::query()->firstOrCreate(
            ['slug' => $slug],
            [
                'name' => $name,
                'email' => $ownerEmail,
                'plan' => 'trial',
                'status' => OrganizationStatus::Trial,
                'trial_ends_at' => now()->addDays(30),
            ],
        );

        app()->instance('currentOrganization', $organization);
        setPermissionsTeamId($organization->id);

        app(RolesAndPermissionsSeeder::class)->seedRolesForOrganization($organization);

        app(OrganizationSubscriptionService::class)->assignTrialPlan($organization, 30);

        $owner = User::query()->firstOrCreate(
            ['email' => $ownerEmail],
            [
                'name' => $ownerName,
                'password' => Hash::make('password123'),
                'default_organization_id' => $organization->id,
            ],
        );

        $owner->organizations()->syncWithoutDetaching([
            $organization->id => ['role' => 'Org Owner'],
        ]);
        $owner->syncRoles(['Org Owner']);

        $this->seedCatalog($organization, $owner->id);

        return $organization;
    }

    protected function seedCatalog(Organization $organization, int $userId): void
    {
        app()->instance('currentOrganization', $organization);
        setPermissionsTeamId($organization->id);

        $category = Category::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'slug' => 'general'],
            ['name' => 'General'],
        );

        $unit = Unit::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'symbol' => 'pcs'],
            ['name' => 'Pieces'],
        );

        $warehouse = Warehouse::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => 'Main Warehouse'],
            ['address' => '100 Demo Street', 'is_default' => true],
        );

        $prefix = strtoupper(substr($organization->slug, 0, 3));

        $products = [
            ['name' => "{$prefix} Widget", 'sku' => "{$prefix}-WGT-001", 'cost' => 10, 'sell' => 19.99, 'reorder' => 5],
            ['name' => "{$prefix} Gadget", 'sku' => "{$prefix}-GDG-001", 'cost' => 25, 'sell' => 49.99, 'reorder' => 3],
            ['name' => "{$prefix} Supply Kit", 'sku' => "{$prefix}-KIT-001", 'cost' => 5, 'sell' => 12.50, 'reorder' => 10],
        ];

        $stockService = app(StockService::class);

        foreach ($products as $item) {
            $product = Product::query()->firstOrCreate(
                [
                    'organization_id' => $organization->id,
                    'sku' => $item['sku'],
                ],
                [
                    'category_id' => $category->id,
                    'unit_id' => $unit->id,
                    'name' => $item['name'],
                    'cost_price' => $item['cost'],
                    'selling_price' => $item['sell'],
                    'tax_rate' => 0,
                    'reorder_point' => $item['reorder'],
                    'is_active' => true,
                ],
            );

            $stockService->recordMovement([
                'warehouse_id' => $warehouse->id,
                'product_id' => $product->id,
                'type' => 'adjustment_in',
                'quantity' => 20,
                'note' => 'Demo seed stock',
                'created_by' => $userId,
            ]);
        }

        Supplier::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => "{$organization->name} Supplier"],
            ['email' => "supplier@{$organization->slug}.test"],
        );

        Customer::query()->firstOrCreate(
            ['organization_id' => $organization->id, 'name' => "{$organization->name} Customer"],
            ['email' => "customer@{$organization->slug}.test"],
        );
    }
}
