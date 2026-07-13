<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public const GUARD = 'api';

    /**
     * Global permission names shared across all organizations.
     *
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            'products.*',
            'warehouses.*',
            'purchase_orders.*',
            'purchase_orders.view',
            'purchase_orders.receive',
            'sales_orders.*',
            'sales_orders.view',
            'sales_orders.confirm',
            'sales_orders.fulfill',
            'sales_orders.pay',
            'sales_orders.deliver',
            'purchase_orders.pay',
            'payments.view',
            'reports.view',
            'users.manage',
        ];
    }

    /**
     * Default organization-scoped roles and the permissions each receives.
     *
     * @return array<string, list<string>>
     */
    public static function rolePermissionMap(): array
    {
        return [
            'Org Owner' => self::permissions(),
            'Manager' => [
                'products.*',
                'warehouses.*',
                'purchase_orders.*',
                'sales_orders.*',
                'payments.view',
                'reports.view',
            ],
            'Warehouse Staff' => [
                'warehouses.*',
                'purchase_orders.view',
                'purchase_orders.receive',
                'sales_orders.view',
                'sales_orders.fulfill',
                'sales_orders.deliver',
            ],
            'Sales Staff' => [
                'sales_orders.*',
                'payments.view',
            ],
            'Viewer' => [
                'reports.view',
            ],
        ];
    }

    /**
     * Seed the global permission catalog.
     */
    public function seedPermissions(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (self::permissions() as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => self::GUARD],
            );
        }
    }

    /**
     * Seed the default roles for a single organization.
     */
    public function seedRolesForOrganization(Organization $organization): void
    {
        $this->seedPermissions();

        setPermissionsTeamId($organization->id);

        foreach (self::rolePermissionMap() as $roleName => $permissionNames) {
            $role = Role::findOrCreate($roleName, self::GUARD);
            $permissions = Permission::query()
                ->where('guard_name', self::GUARD)
                ->whereIn('name', $permissionNames)
                ->get();

            $role->syncPermissions($permissions);
        }
    }

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->seedPermissions();
    }
}
