<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\OrganizationSubscription;
use App\Models\Plan;
use Illuminate\Support\Facades\DB;

class OrganizationSubscriptionService
{
    public function assignTrialPlan(Organization $organization, ?int $trialDays = 14): OrganizationSubscription
    {
        $plan = Plan::query()->where('slug', 'trial')->firstOrFail();

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

        return $subscription->plan->limits ?? [];
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
}
