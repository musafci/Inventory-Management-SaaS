<?php

namespace App\Models;

use App\Enums\OrganizationStatus;
use Database\Factories\OrganizationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['name', 'slug', 'email', 'phone', 'plan', 'status', 'trial_ends_at', 'stripe_customer_id', 'deletion_requested_at', 'deletion_scheduled_for'])]
class Organization extends Model
{
    /** @use HasFactory<OrganizationFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => OrganizationStatus::class,
            'trial_ends_at' => 'datetime',
            'deletion_requested_at' => 'datetime',
            'deletion_scheduled_for' => 'datetime',
        ];
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class)
            ->withPivot('role')
            ->withTimestamps();
    }

    public function subscription(): HasOne
    {
        return $this->hasOne(OrganizationSubscription::class);
    }

    public function supportNotes(): HasMany
    {
        return $this->hasMany(SupportNote::class);
    }

    public function featureFlagOverrides(): HasMany
    {
        return $this->hasMany(OrganizationFeatureFlag::class);
    }

    public function isSuspended(): bool
    {
        return $this->status === OrganizationStatus::Suspended;
    }
}
