<?php

namespace App\Policies;

use App\Models\Organization;
use App\Models\User;

class OrganizationPolicy
{
    /**
     * Determine whether the user can update organization settings.
     */
    public function update(User $user, Organization $organization): bool
    {
        setPermissionsTeamId($organization->id);

        return $user->hasRole('Org Owner');
    }
}
