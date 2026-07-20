<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Organization\UpdateOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Services\OrganizationService;
use Illuminate\Http\JsonResponse;

class OrganizationController extends ApiController
{
    public function __construct(
        protected OrganizationService $organizationService,
    ) {}

    public function show(): JsonResponse
    {
        /** @var Organization $organization */
        $organization = app('currentOrganization');
        $organization->loadCount('users');

        $this->authorize('view', $organization);

        return $this->success(new OrganizationResource($organization));
    }

    public function update(UpdateOrganizationRequest $request): JsonResponse
    {
        /** @var Organization $organization */
        $organization = app('currentOrganization');

        $this->authorize('update', $organization);

        $organization = $this->organizationService->update($organization, $request->validated());
        $organization->loadCount('users');

        return $this->success(new OrganizationResource($organization));
    }
}
