<?php

namespace App\Policies;

use App\Models\User;
use App\Models\Warehouse;

class WarehousePolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.create');
    }

    public function update(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.update');
    }

    public function delete(User $user, Warehouse $warehouse): bool
    {
        return $user->can('inventory.delete');
    }
}
