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
            'sales_orders.*',
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
                'reports.view',
            ],
            'Warehouse Staff' => [
                'warehouses.*',
            ],
            'Sales Staff' => [
                'sales_orders.*',
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
        foreach (self::permissions() as $permission) {
            Permission::findOrCreate($permission, self::GUARD);
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
