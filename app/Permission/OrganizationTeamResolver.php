<?php

namespace App\Permission;

use Illuminate\Database\Eloquent\Model;
use Spatie\Permission\Contracts\PermissionsTeamResolver;

class OrganizationTeamResolver implements PermissionsTeamResolver
{
    protected int|string|null $teamId = null;

    public function setPermissionsTeamId(int|string|Model|null $id): void
    {
        if ($id instanceof Model) {
            $id = $id->getKey();
        }

        $this->teamId = $id;
    }

    public function getPermissionsTeamId(): int|string|null
    {
        if (app()->bound('currentOrganization')) {
            return app('currentOrganization')->id;
        }

        return $this->teamId;
    }
}
