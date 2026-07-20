<?php

namespace App\Http\Livewire\Concerns;

use App\Support\OrganizationSession;

trait InteractsWithOrganizationSession
{
    /**
     * @return array<string, mixed>|null
     */
    protected function currentOrganizationFromSession(): ?array
    {
        return OrganizationSession::currentOrganization();
    }

    protected function currentOrganizationRole(): ?string
    {
        return OrganizationSession::currentRole();
    }

    protected function canManageOrganization(): bool
    {
        return OrganizationSession::canManageOrganization();
    }

    protected function canManageUsers(): bool
    {
        return OrganizationSession::canManageUsers();
    }
}
