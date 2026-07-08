<?php

namespace App\Traits;

use App\Models\Organization;
use App\Models\Scopes\OrganizationScope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

trait BelongsToOrganization
{
    public static function bootBelongsToOrganization(): void
    {
        static::addGlobalScope(new OrganizationScope);

        static::creating(function (Model $model): void {
            if (
                empty($model->organization_id)
                && app()->bound('currentOrganization')
            ) {
                $model->organization_id = app('currentOrganization')->id;
            }
        });
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function scopeWithoutOrganizationScope(Builder $query): Builder
    {
        return $query->withoutGlobalScope(OrganizationScope::class);
    }
}
