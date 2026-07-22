<?php

namespace App\Models;

use App\Enums\SubscriptionStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['organization_id', 'plan_id', 'status', 'trial_ends_at', 'current_period_ends_at', 'stripe_subscription_id', 'billing_interval', 'past_due_at', 'trial_reminder_sent_at'])]
class OrganizationSubscription extends Model
{
    protected function casts(): array
    {
        return [
            'status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'current_period_ends_at' => 'datetime',
            'past_due_at' => 'datetime',
            'trial_reminder_sent_at' => 'datetime',
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
            SubscriptionStatus::PastDue,
            SubscriptionStatus::Cancelled,
        ], true);
    }

    public function permitsWriteAccess(): bool
    {
        if (in_array($this->status, [SubscriptionStatus::Trial, SubscriptionStatus::Active], true)) {
            return true;
        }

        if ($this->status === SubscriptionStatus::PastDue) {
            return ! $this->pastDueGraceExpired();
        }

        return false;
    }

    public function pastDueGraceExpired(): bool
    {
        if ($this->status !== SubscriptionStatus::PastDue || $this->past_due_at === null) {
            return false;
        }

        $graceDays = (int) config('subscription.past_due_grace_days', 7);

        return $this->past_due_at->copy()->addDays($graceDays)->isPast();
    }
}
