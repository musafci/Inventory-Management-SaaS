<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'plan_id', 'status', 'trial_ends_at', 'current_period_ends_at', 'stripe_subscription_id', 'billing_interval'])]
class OrganizationSubscription extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
        ];
    }

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function isActive(): bool
    {
        return in_array($this->status, [SubscriptionStatus::Trial, SubscriptionStatus::Active], true);
    }

    public function permitsReadAccess(): bool
    {
        return in_array($this->status, [
            SubscriptionStatus::Trial,
            SubscriptionStatus::Active,
            SubscriptionStatus::Expired,
        ], true);
    }

    public function permitsWriteAccess(): bool
    {
        return in_array($this->status, [
            SubscriptionStatus::Trial,
            SubscriptionStatus::Active,
        ], true);
    }
}
