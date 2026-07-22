<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Billing\CreateCheckoutSessionRequest;
use App\Http\Resources\BillingOverviewResource;
use App\Models\Organization;
use App\Services\StripeBillingService;
use Illuminate\Http\JsonResponse;
use RuntimeException;

class BillingController extends ApiController
{
    public function __construct(
        protected StripeBillingService $billingService,
    ) {}

    public function show(): JsonResponse
    {
        /** @var Organization $organization */
        $organization = app('currentOrganization');

        $this->authorize('update', $organization);

        $overview = $this->billingService->billingOverview($organization);

        return $this->success(new BillingOverviewResource($overview));
    }

    public function checkout(CreateCheckoutSessionRequest $request): JsonResponse
    {
        /** @var Organization $organization */
        $organization = app('currentOrganization');

        $this->authorize('update', $organization);

        try {
            $session = $this->billingService->createCheckoutSession(
                $organization,
                $request->validated('plan_slug'),
                $request->validated('interval'),
            );
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), [], 422);
        }

        return $this->success($session);
    }

    public function portal(): JsonResponse
    {
        /** @var Organization $organization */
        $organization = app('currentOrganization');

        $this->authorize('update', $organization);

        try {
            $session = $this->billingService->createPortalSession($organization);
        } catch (RuntimeException $exception) {
            return $this->error($exception->getMessage(), [], 422);
        }

        return $this->success($session);
    }
}
