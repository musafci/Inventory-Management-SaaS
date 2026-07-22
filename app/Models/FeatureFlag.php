<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['key', 'description', 'default_enabled'])]
class FeatureFlag extends Model
{
    protected function casts(): array
    {
        return [
            'default_enabled' => 'boolean',
        ];
    }

    public function organizationOverrides(): HasMany
    {
        return $this->hasMany(OrganizationFeatureFlag::class);
    }
}
