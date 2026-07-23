<?php

namespace App\Jobs;

use App\Mail\OrganizationPlanUpgradedMail;
use App\Models\Organization;
use App\Models\User;
use App\Permission\PermissionCatalog;
use App\Services\OrganizationSubscriptionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendOrganizationPlanUpgradedNotificationJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public int $organizationId,
        public ?string $previousPlanSlug = null,
        public ?string $previousStatus = null,
    ) {}

    public function handle(OrganizationSubscriptionService $subscriptionService): void
    {
        $recipient = config('organization.registration_notification_email');

        if (! is_string($recipient) || $recipient === '') {
            return;
        }

        $organization = Organization::query()->find($this->organizationId);

        if ($organization === null) {
            return;
        }

        $subscription = $subscriptionService->activeSubscription($organization)?->load('plan');

        if ($subscription === null || $subscription->plan === null) {
            return;
        }

        $owner = $organization->users()
            ->wherePivot('role', PermissionCatalog::ORG_OWNER_ROLE)
            ->orderBy('organization_user.created_at')
            ->first();

        if ($owner === null) {
            $owner = $organization->users()->orderBy('organization_user.created_at')->first();
        }

        Mail::to($recipient)->send(new OrganizationPlanUpgradedMail(
            $organization,
            $subscription,
            $owner instanceof User ? $owner : null,
            $this->previousPlanSlug,
            $this->previousStatus,
        ));
    }
}
