<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Platform\UpdateOrganizationFeatureFlagRequest;
use App\Http\Resources\FeatureFlagResource;
use App\Models\FeatureFlag;
use App\Models\Organization;
use App\Services\FeatureFlagService;
use Illuminate\Http\JsonResponse;

class PlatformFeatureFlagController extends ApiController
{
    public function __construct(
        protected FeatureFlagService $featureFlagService,
    ) {}

    public function index(int $organizationId): JsonResponse
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $flags = $this->featureFlagService->flagsForOrganization($organization);

        return $this->success(FeatureFlagResource::collection($flags));
    }

    public function update(
        UpdateOrganizationFeatureFlagRequest $request,
        int $organizationId,
        int $featureFlagId,
    ): JsonResponse {
        $organization = Organization::query()->findOrFail($organizationId);
        $flag = FeatureFlag::query()->findOrFail($featureFlagId);

        $this->featureFlagService->setOverride(
            $organization,
            $flag,
            (bool) $request->validated('enabled'),
        );

        $updated = $this->featureFlagService->flagsForOrganization($organization)
            ->firstWhere('id', $flag->id);

        return $this->success(new FeatureFlagResource($updated));
    }
}
