<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\Role;
use App\Permission\PermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;

class RolesAndPermissionsSeeder extends Seeder
{
    public const GUARD = 'api';

    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        return PermissionCatalog::all();
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rolePermissionMap(): array
    {
        return PermissionCatalog::defaultRolePermissions();
    }

    public function seedPermissions(): void
    {
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        $validNames = collect(self::permissions());

        Permission::query()
            ->where('guard_name', self::GUARD)
            ->whereNotIn('name', $validNames)
            ->delete();

        foreach (self::permissions() as $permission) {
            Permission::query()->firstOrCreate(
                ['name' => $permission, 'guard_name' => self::GUARD],
            );
        }
    }

    public function seedRolesForOrganization(Organization $organization): void
    {
        $this->seedPermissions();

        setPermissionsTeamId($organization->id);

        $metadata = PermissionCatalog::defaultRoleMetadata();

        foreach (self::rolePermissionMap() as $roleName => $permissionNames) {
            $roleMeta = $metadata[$roleName] ?? [
                'description' => null,
                'is_protected' => false,
                'is_system' => false,
            ];

            $role = Role::query()->firstOrCreate(
                [
                    'name' => $roleName,
                    'guard_name' => self::GUARD,
                    'organization_id' => $organization->id,
                ],
                [
                    'description' => $roleMeta['description'],
                    'is_protected' => $roleMeta['is_protected'],
                    'is_system' => $roleMeta['is_system'],
                ],
            );

            $role->fill([
                'description' => $roleMeta['description'],
                'is_protected' => $roleMeta['is_protected'],
                'is_system' => $roleMeta['is_system'],
            ])->save();

            $permissions = Permission::query()
                ->where('guard_name', self::GUARD)
                ->whereIn('name', $permissionNames)
                ->get();

            if (! $role->isProtected()) {
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions(Permission::query()->where('guard_name', self::GUARD)->get());
            }
        }
    }

    public function run(): void
    {
        $this->seedPermissions();
    }
}
