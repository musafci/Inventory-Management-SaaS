<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Platform\UpdatePlatformOrganizationRequest;
use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use Illuminate\Http\JsonResponse;

class PlatformOrganizationController extends ApiController
{
    public function index(): JsonResponse
    {
        $organizations = Organization::query()
            ->with(['subscription.plan'])
            ->withCount('users')
            ->orderBy('name')
            ->paginate(request()->integer('per_page', 15));

        return $this->success(
            OrganizationResource::collection($organizations->items()),
            [
                'pagination' => [
                    'current_page' => $organizations->currentPage(),
                    'per_page' => $organizations->perPage(),
                    'total' => $organizations->total(),
                    'last_page' => $organizations->lastPage(),
                ],
            ],
        );
    }

    public function show(int $organizationId): JsonResponse
    {
        $organization = Organization::query()
            ->with(['subscription.plan', 'users'])
            ->withCount('users')
            ->findOrFail($organizationId);

        return $this->success(new OrganizationResource($organization));
    }

    public function update(UpdatePlatformOrganizationRequest $request, int $organizationId): JsonResponse
    {
        $organization = Organization::query()->findOrFail($organizationId);

        $organization->fill($request->validated());
        $organization->save();

        return $this->success(new OrganizationResource($organization->fresh()));
    }
}
