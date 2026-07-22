<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'slug',
    'name',
    'price_monthly',
    'price_annual',
    'limits',
    'is_custom',
    'grace_buffer_percent',
    'sort_order',
    'is_active',
])]
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_annual' => 'decimal:2',
            'limits' => 'array',
            'is_custom' => 'boolean',
            'grace_buffer_percent' => 'integer',
            'sort_order' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(OrganizationSubscription::class);
    }

    public function limit(string $key): ?int
    {
        $limits = $this->limits ?? [];
        $value = $limits[$key] ?? null;

        if ($value === null) {
            return null;
        }

        return (int) $value;
    }

    public function apiRateLimitPerMinute(): ?int
    {
        $limits = $this->limits ?? [];

        $value = $limits['api_rate_limit_per_minute']
            ?? $limits['api_rate_limit']
            ?? null;

        if ($value === null) {
            return null;
        }

        return (int) $value;
    }
}
