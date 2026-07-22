<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;
use App\Permission\PermissionCatalog;

class OrganizationPolicy
{
    public function view(User $user, Organization $organization): bool
    {
        setPermissionsTeamId($organization->id);

        return $user->can('settings.view')
            || $user->hasRole(PermissionCatalog::SYSTEM_OWNER_ROLE);
    }

    public function update(User $user, Organization $organization): bool
    {
        setPermissionsTeamId($organization->id);

        return $user->can('settings.update')
            || $user->hasRole(PermissionCatalog::SYSTEM_OWNER_ROLE);
    }

    public function exportData(User $user, Organization $organization): bool
    {
        setPermissionsTeamId($organization->id);

        return $user->hasRole(PermissionCatalog::ORG_OWNER_ROLE);
    }

    public function requestDeletion(User $user, Organization $organization): bool
    {
        setPermissionsTeamId($organization->id);

        return $user->hasRole(PermissionCatalog::ORG_OWNER_ROLE);
    }

    public function cancelDeletion(User $user, Organization $organization): bool
    {
        setPermissionsTeamId($organization->id);

        return $user->hasRole(PermissionCatalog::ORG_OWNER_ROLE);
    }
}
