<?php

namespace App\Policies;

use App\Models\Unit;
use App\Models\User;

class UnitPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Unit $unit): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.create');
    }

    public function update(User $user, Unit $unit): bool
    {
        return $user->can('inventory.update');
    }

    public function delete(User $user, Unit $unit): bool
    {
        return $user->can('inventory.delete');
    }
}
