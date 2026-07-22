<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Requests\Platform\StorePlatformAdminRequest;
use App\Http\Resources\PlatformAdminResource;
use App\Models\PlatformAdmin;
use App\Services\PlatformAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class PlatformAdminController extends ApiController
{
    public function __construct(
        protected PlatformAdminService $platformAdminService,
    ) {}

    public function index(): JsonResponse
    {
        $admins = $this->platformAdminService->paginate();

        return $this->success(
            PlatformAdminResource::collection($admins->items()),
            [
                'pagination' => [
                    'current_page' => $admins->currentPage(),
                    'per_page' => $admins->perPage(),
                    'total' => $admins->total(),
                    'last_page' => $admins->lastPage(),
                ],
            ],
        );
    }

    public function store(StorePlatformAdminRequest $request): JsonResponse
    {
        $admin = $this->platformAdminService->create(
            $request->validated('name'),
            $request->validated('email'),
            $request->validated('password'),
        );

        return $this->success(new PlatformAdminResource($admin), status: Response::HTTP_CREATED);
    }

    public function destroy(int $adminId): Response
    {
        $admin = PlatformAdmin::query()->findOrFail($adminId);
        $this->platformAdminService->delete($admin);

        return response()->noContent();
    }
}
