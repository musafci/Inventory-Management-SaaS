<?php

namespace App\Services;

use App\Models\Organization;
use App\Models\Role;
use App\Models\User;
use App\Permission\PermissionCatalog;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RoleManagementService
{
    public function __construct(
        protected PermissionAuthorizationService $permissionAuthorization,
    ) {}

    /**
     * @return Collection<int, Role>
     */
    public function listRoles(): Collection
    {
        $organization = app('currentOrganization');

        return Role::query()
            ->where('organization_id', $organization->id)
            ->with('permissions')
            ->orderByDesc('is_protected')
            ->orderByDesc('is_system')
            ->orderBy('name')
            ->get()
            ->map(function (Role $role) use ($organization): Role {
                $role->users_count = DB::table('model_has_roles')
                    ->where('role_id', $role->id)
                    ->where('organization_id', $organization->id)
                    ->count();

                return $role;
            });
    }

    /**
     * @return list<string>
     */
    public function assignableRoleNames(): array
    {
        $organization = app('currentOrganization');

        return Role::query()
            ->where('organization_id', $organization->id)
            ->where('is_protected', false)
            ->orderBy('name')
            ->pluck('name')
            ->all();
    }

    public function assertAssignableRole(string $roleName): void
    {
        if (! in_array($roleName, $this->assignableRoleNames(), true)) {
            throw ValidationException::withMessages([
                'role' => ['Invalid organization role.'],
            ]);
        }
    }

    /**
     * @return array<string, list<string>>
     */
    public function groupedPermissions(): array
    {
        return PermissionCatalog::groups();
    }

    public function create(array $data): Role
    {
        $organization = app('currentOrganization');

        $this->assertRoleNameAvailable($organization, $data['name']);

        return DB::transaction(function () use ($organization, $data): Role {
            $role = Role::query()->create([
                'name' => $data['name'],
                'guard_name' => RolesAndPermissionsSeeder::GUARD,
                'organization_id' => $organization->id,
                'description' => $data['description'] ?? null,
                'is_protected' => false,
                'is_system' => false,
            ]);

            $this->syncPermissions($role, $data['permissions'] ?? []);

            return $role->fresh(['permissions']);
        });
    }

    public function update(Role $role, array $data): Role
    {
        $this->assertRoleIsMutable($role, updating: true);

        return DB::transaction(function () use ($role, $data): Role {
            if (isset($data['name'])) {
                $this->assertRoleNameAvailable(app('currentOrganization'), $data['name'], $role->id);
                $role->name = $data['name'];
            }

            if (array_key_exists('description', $data)) {
                $role->description = $data['description'];
            }

            $role->save();

            if (array_key_exists('permissions', $data)) {
                $this->syncPermissions($role, $data['permissions']);
            }

            app(PermissionRegistrar::class)->forgetCachedPermissions();

            return $role->fresh(['permissions']);
        });
    }

    public function delete(Role $role): void
    {
        $this->assertRoleIsMutable($role, updating: false);

        if (DB::table('model_has_roles')->where('role_id', $role->id)->where('organization_id', $role->organization_id)->count() > 0) {
            throw ValidationException::withMessages([
                'role' => ['Cannot delete a role that is assigned to users.'],
            ]);
        }

        DB::transaction(function () use ($role): void {
            $role->permissions()->detach();
            $role->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        });
    }

    /**
     * @param  list<string>  $permissionNames
     */
    protected function syncPermissions(Role $role, array $permissionNames): void
    {
        if ($role->isProtected()) {
            throw ValidationException::withMessages([
                'permissions' => ['Protected roles cannot be modified.'],
            ]);
        }

        $valid = collect($permissionNames)
            ->intersect(PermissionCatalog::all())
            ->values()
            ->all();

        $permissions = Permission::query()
            ->where('guard_name', RolesAndPermissionsSeeder::GUARD)
            ->whereIn('name', $valid)
            ->get();

        $role->syncPermissions($permissions);

        $userIds = DB::table('model_has_roles')
            ->where('role_id', $role->id)
            ->where('organization_id', $role->organization_id)
            ->pluck('model_id');

        foreach ($userIds as $userId) {
            $this->permissionAuthorization->forgetUserOrganizationCache(
                (int) $userId,
                (int) $role->organization_id,
            );
        }
    }

    protected function assertRoleIsMutable(Role $role, bool $updating): void
    {
        if ($role->organization_id !== app('currentOrganization')->id) {
            throw ValidationException::withMessages([
                'role' => ['Role does not belong to the active organization.'],
            ]);
        }

        if ($role->isProtected()) {
            throw ValidationException::withMessages([
                'role' => ['This role is protected and cannot be changed.'],
            ]);
        }
    }

    protected function assertRoleNameAvailable(Organization $organization, string $name, ?int $ignoreRoleId = null): void
    {
        if (in_array($name, PermissionCatalog::protectedRoleNames(), true) && $name !== PermissionCatalog::SYSTEM_OWNER_ROLE) {
            throw ValidationException::withMessages([
                'name' => ['This role name is reserved.'],
            ]);
        }

        $exists = Role::query()
            ->where('organization_id', $organization->id)
            ->where('name', $name)
            ->when($ignoreRoleId, fn ($query) => $query->whereKeyNot($ignoreRoleId))
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['A role with this name already exists in the organization.'],
            ]);
        }
    }
}
