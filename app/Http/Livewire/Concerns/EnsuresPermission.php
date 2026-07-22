<?php

namespace App\Http\Livewire\Concerns;

use App\Support\OrganizationSession;

trait EnsuresPermission
{
    protected function ensurePermission(string $permission): void
    {
        if (! OrganizationSession::can($permission)) {
            abort(403, 'You do not have permission to access this page.');
        }
    }

    /**
     * @param  list<string>  $permissions
     */
    protected function ensureAnyPermission(array $permissions): void
    {
        if (! OrganizationSession::canAny($permissions)) {
            abort(403, 'You do not have permission to access this page.');
        }
    }
}
