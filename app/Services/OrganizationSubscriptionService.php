<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Exceptions\SubscriptionAccessDeniedException;
use App\Exceptions\SubscriptionPaymentRequiredException;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class OrganizationSubscriptionService
{
    public function assignTrialPlan(Organization $organization, ?int $trialDays = null): OrganizationSubscription
    {
        $trialDays ??= (int) config('subscription.trial_days', 14);

        $plan = Plan::query()
            ->where('slug', config('subscription.trial_plan_slug', 'growth'))
            ->firstOrFail();

        return DB::transaction(function () use ($organization, $plan, $trialDays): OrganizationSubscription {
            $subscription = OrganizationSubscription::query()->updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'plan_id' => $plan->id,
                    'status' => SubscriptionStatus::Trial,
                    'trial_ends_at' => now()->addDays($trialDays),
                    'current_period_ends_at' => now()->addDays($trialDays),
                ],
            );

            $this->syncOrganizationPlanCache($organization->fresh(), $subscription);

            return $subscription->load('plan');
        });
    }

    public function activeSubscription(Organization $organization): ?OrganizationSubscription
    {
        return OrganizationSubscription::query()
            ->with('plan')
            ->where('organization_id', $organization->id)
            ->first();
    }

    /**
     * @return array<string, int|null>
     */
    public function activeLimits(Organization $organization): array
    {
        $subscription = $this->activeSubscription($organization);

        if ($subscription === null || $subscription->plan === null) {
            return [];
        }

        if (! in_array($subscription->status, [
            SubscriptionStatus::Trial,
            SubscriptionStatus::Active,
            SubscriptionStatus::Expired,
        ], true)) {
            return [];
        }

        return $subscription->plan->limits ?? [];
    }

    public function graceBufferPercent(Organization $organization): int
    {
        $subscription = $this->activeSubscription($organization);

        return max(0, (int) ($subscription?->plan?->grace_buffer_percent ?? 10));
    }

    public function apiRateLimitPerMinute(Organization $organization): int
    {
        $subscription = $this->activeSubscription($organization);
        $planLimit = $subscription?->plan?->apiRateLimitPerMinute();

        if ($planLimit !== null) {
            return max(1, $planLimit);
        }

        return (int) config('api.rate_limit_per_minute', 120);
    }

    /**
     * @throws SubscriptionAccessDeniedException
     */
    public function assertAllowsTenantRead(Organization $organization): void
    {
        $subscription = $this->activeSubscription($organization);

        if ($subscription === null) {
            throw new SubscriptionAccessDeniedException(
                'This organization has no subscription. Contact platform support.',
            );
        }

        $this->expireTrialIfNeeded($subscription);
        $subscription = $subscription->fresh(['plan']) ?? $subscription;

        if ($subscription->status === SubscriptionStatus::Cancelled) {
            throw new SubscriptionAccessDeniedException(
                'This organization subscription has been cancelled.',
            );
        }

        if ($subscription->status === SubscriptionStatus::PastDue) {
            throw new SubscriptionAccessDeniedException(
                'Your subscription payment is past due. Please update billing to continue.',
            );
        }

        if (! $subscription->permitsReadAccess()) {
            throw new SubscriptionAccessDeniedException(
                'This organization subscription is not active.',
            );
        }
    }

    /**
     * @throws SubscriptionAccessDeniedException
     * @throws SubscriptionPaymentRequiredException
     */
    public function assertAllowsTenantWrite(Organization $organization): void
    {
        $this->assertAllowsTenantRead($organization);

        $subscription = $this->activeSubscription($organization);

        if ($subscription === null) {
            throw new SubscriptionAccessDeniedException(
                'This organization has no subscription. Contact platform support.',
            );
        }

        if ($subscription->status === SubscriptionStatus::Expired) {
            throw new SubscriptionPaymentRequiredException(
                'Your trial has ended. Choose a plan to continue making changes.',
            );
        }

        if (! $subscription->permitsWriteAccess()) {
            throw new SubscriptionAccessDeniedException(
                'This organization subscription does not allow changes.',
            );
        }
    }

    /**
     * @throws SubscriptionAccessDeniedException
     * @throws SubscriptionPaymentRequiredException
     */
    public function assertAllowsTenantAccess(Organization $organization): void
    {
        $this->assertAllowsTenantWrite($organization);
    }

    public function subscriptionPermitsAccess(OrganizationSubscription $subscription): bool
    {
        $subscription = $this->expireTrialIfNeeded($subscription);

        return $subscription->permitsWriteAccess();
    }

    public function expireTrialIfNeeded(OrganizationSubscription $subscription): OrganizationSubscription
    {
        if ($subscription->status !== SubscriptionStatus::Trial) {
            return $subscription;
        }

        if ($subscription->trial_ends_at === null || $subscription->trial_ends_at->isFuture()) {
            return $subscription;
        }

        $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();

        return $subscription->fresh(['plan']) ?? $subscription;
    }

    public function expireDueTrials(): int
    {
        $expired = 0;

        OrganizationSubscription::query()
            ->where('status', SubscriptionStatus::Trial)
            ->whereNotNull('trial_ends_at')
            ->where('trial_ends_at', '<=', now())
            ->each(function (OrganizationSubscription $subscription) use (&$expired): void {
                $subscription->forceFill(['status' => SubscriptionStatus::Expired])->save();
                $expired++;
            });

        return $expired;
    }

    public function updateSubscription(
        Organization $organization,
        Plan $plan,
        SubscriptionStatus $status,
        ?\DateTimeInterface $trialEndsAt = null,
        ?\DateTimeInterface $periodEndsAt = null,
    ): OrganizationSubscription {
        return DB::transaction(function () use ($organization, $plan, $status, $trialEndsAt, $periodEndsAt): OrganizationSubscription {
            $subscription = OrganizationSubscription::query()->updateOrCreate(
                ['organization_id' => $organization->id],
                [
                    'plan_id' => $plan->id,
                    'status' => $status,
                    'trial_ends_at' => $trialEndsAt,
                    'current_period_ends_at' => $periodEndsAt ?? $trialEndsAt,
                ],
            );

            $this->syncOrganizationPlanCache($organization->fresh(), $subscription);

            return $subscription->load('plan');
        });
    }

    public function syncOrganizationPlanCache(Organization $organization, ?OrganizationSubscription $subscription = null): void
    {
        $subscription ??= $this->activeSubscription($organization);

        if ($subscription?->plan === null) {
            return;
        }

        $organization->forceFill([
            'plan' => $subscription->plan->slug,
            'trial_ends_at' => $subscription->trial_ends_at ?? $organization->trial_ends_at,
        ])->save();
    }

    /**
     * Ensure every organization has a subscription row (for legacy data).
     */
    public function backfillMissingSubscriptions(): int
    {
        $created = 0;

        Organization::query()
            ->whereDoesntHave('subscription')
            ->each(function (Organization $organization) use (&$created): void {
                $trialDays = (int) config('subscription.trial_days', 14);

                if ($organization->trial_ends_at !== null && $organization->trial_ends_at->isFuture()) {
                    $trialDays = max(1, (int) now()->diffInDays($organization->trial_ends_at));
                }

                $this->assignTrialPlan($organization, $trialDays);
                $created++;
            });

        return $created;
    }
}
