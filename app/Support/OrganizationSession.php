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

    public static function canManageOrganization(): bool
    {
        return self::currentRole() === 'Org Owner';
    }

    public static function canManageUsers(): bool
    {
        return in_array(self::currentRole(), ['Org Owner', 'Admin'], true);
    }

    public static function canAccessSettings(): bool
    {
        return self::canManageOrganization() || self::canManageUsers();
    }
}
