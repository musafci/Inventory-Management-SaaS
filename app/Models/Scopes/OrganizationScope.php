<?php

namespace App\Models\Scopes;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class OrganizationScope implements Scope
{
    /**
     * Scope queries to the current organization, or return nothing when
     * no tenant context is bound (fail closed).
     */
    public function apply(Builder $builder, Model $model): void
    {
        if (! app()->bound('currentOrganization')) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $builder->where(
            $model->qualifyColumn('organization_id'),
            app('currentOrganization')->id,
        );
    }
}
