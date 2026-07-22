<?php

namespace App\Policies;

use App\Models\Role;
use App\Models\User;

class RolePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('settings.manage_roles');
    }

    public function view(User $user, Role $role): bool
    {
        return $user->can('settings.manage_roles')
            && (int) $role->organization_id === (int) app('currentOrganization')->id;
    }

    public function create(User $user): bool
    {
        return $user->can('settings.manage_roles');
    }

    public function update(User $user, Role $role): bool
    {
        return $user->can('settings.manage_roles')
            && (int) $role->organization_id === (int) app('currentOrganization')->id
            && ! $role->isProtected();
    }

    public function delete(User $user, Role $role): bool
    {
        return $this->update($user, $role);
    }
}
