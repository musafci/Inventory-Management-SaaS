<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Platform\UpdatePlatformOrganizationSubscriptionRequest;
use App\Http\Resources\OrganizationSubscriptionResource;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\OrganizationSubscriptionService;
use App\Enums\SubscriptionStatus;
use Illuminate\Http\JsonResponse;

class PlatformOrganizationSubscriptionController extends ApiController
{
    public function __construct(
        protected OrganizationSubscriptionService $subscriptionService,
    ) {}

    public function show(int $organizationId): JsonResponse
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $subscription = $this->subscriptionService->activeSubscription($organization);

        if ($subscription === null) {
            return $this->success(null);
        }

        return $this->success(new OrganizationSubscriptionResource($subscription));
    }

    public function update(UpdatePlatformOrganizationSubscriptionRequest $request, int $organizationId): JsonResponse
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $plan = Plan::query()->findOrFail($request->validated('plan_id'));

        $subscription = $this->subscriptionService->updateSubscription(
            $organization,
            $plan,
            SubscriptionStatus::from($request->validated('status', SubscriptionStatus::Active->value)),
            $request->date('trial_ends_at'),
            $request->date('current_period_ends_at'),
        );

        return $this->success(new OrganizationSubscriptionResource($subscription));
    }
}
