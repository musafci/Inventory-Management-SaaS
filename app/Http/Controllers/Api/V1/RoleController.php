<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Role\StoreRoleRequest;
use App\Http\Requests\Role\UpdateRoleRequest;
use App\Http\Resources\RoleResource;
use App\Models\Role;
use App\Services\RoleManagementService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class RoleController extends ApiController
{
    public function __construct(
        protected RoleManagementService $roleManagementService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return $this->success(
            RoleResource::collection($this->roleManagementService->listRoles()),
        );
    }

    public function permissions(): JsonResponse
    {
        $this->authorize('viewAny', Role::class);

        return $this->success($this->roleManagementService->groupedPermissions());
    }

    public function store(StoreRoleRequest $request): JsonResponse
    {
        $this->authorize('create', Role::class);

        $role = $this->roleManagementService->create($request->validated());

        return $this->success(new RoleResource($role), status: 201);
    }

    public function update(UpdateRoleRequest $request, int $roleId): JsonResponse
    {
        $role = $this->findRoleForCurrentOrganization($roleId);

        $this->authorize('update', $role);

        $role = $this->roleManagementService->update($role, $request->validated());

        return $this->success(new RoleResource($role));
    }

    public function destroy(int $roleId): Response
    {
        $role = $this->findRoleForCurrentOrganization($roleId);

        $this->authorize('delete', $role);

        $this->roleManagementService->delete($role);

        return response()->noContent();
    }

    protected function findRoleForCurrentOrganization(int $roleId): Role
    {
        return Role::query()
            ->whereKey($roleId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
