<?php

namespace App\Policies;

use App\Models\Category;
use App\Models\User;

class CategoryPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('inventory.view');
    }

    public function view(User $user, Category $category): bool
    {
        return $user->can('inventory.view');
    }

    public function create(User $user): bool
    {
        return $user->can('inventory.create');
    }

    public function update(User $user, Category $category): bool
    {
        return $user->can('inventory.update');
    }

    public function delete(User $user, Category $category): bool
    {
        return $user->can('inventory.delete');
    }
}
