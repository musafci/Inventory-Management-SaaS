<?php

namespace App\Support;

class OrganizationSession
{
    /**
     * @return array<string, mixed>|null
     */
    public static function currentOrganization(): ?array
    {
        $organizationId = (int) session('organization_id', 0);

        if ($organizationId === 0) {
            return null;
        }

        $organization = collect(session('organizations', []))
            ->first(fn (array $org): bool => (int) ($org['id'] ?? 0) === $organizationId);

        return is_array($organization) ? $organization : null;
    }

    public static function currentRole(): ?string
    {
        $organization = self::currentOrganization();

        if ($organization === null) {
            return null;
        }

        return $organization['role']
            ?? ($organization['pivot']['role'] ?? null);
    }

    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        $permissions = session('permissions', []);

        return is_array($permissions) ? array_values($permissions) : [];
    }

    public static function can(string $permission): bool
    {
        if (self::hasRole(\App\Permission\PermissionCatalog::SYSTEM_OWNER_ROLE)) {
            return true;
        }

        return in_array($permission, self::permissions(), true);
    }

    /**
     * @param  list<string>  $permissions
     */
    public static function canAny(array $permissions): bool
    {
        foreach ($permissions as $permission) {
            if (self::can($permission)) {
                return true;
            }
        }

        return false;
    }

    public static function hasRole(string $role): bool
    {
        return self::currentRole() === $role;
    }

    public static function canManageOrganization(): bool
    {
        return self::can('settings.update');
    }

    public static function canManageUsers(): bool
    {
        return self::can('settings.manage_users');
    }

    public static function canManageRoles(): bool
    {
        return self::can('settings.manage_roles');
    }

    public static function canAccessSettings(): bool
    {
        return self::canAny([
            'settings.view',
            'settings.update',
            'settings.manage_users',
            'settings.manage_roles',
        ]);
    }
}
