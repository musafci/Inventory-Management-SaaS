<?php

namespace App\Policies;

use App\Models\StockMovement;
use App\Models\User;

class StockMovementPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('warehouses.view');
    }

    public function view(User $user, StockMovement $stockMovement): bool
    {
        return $user->can('warehouses.view');
    }

    public function create(User $user): bool
    {
        return $user->can('warehouses.update');
    }
}
