<?php

namespace App\Http\Controllers\Api\Platform\V1;

use App\Http\Controllers\Api\V1\ApiController;
use App\Http\Resources\ActivityLogResource;
use App\Models\Organization;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;

class PlatformActivityLogController extends ApiController
{
    public function __construct(
        protected ActivityLogService $activityLogService,
    ) {}

    public function index(): JsonResponse
    {
        $logs = $this->activityLogService->paginatePlatformWide();

        return $this->success(
            ActivityLogResource::collection($logs->items()),
            [
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
                'filters' => [
                    'subject_types' => $this->activityLogService->subjectTypeOptions(),
                ],
            ],
        );
    }

    public function summary(): JsonResponse
    {
        $organizationId = request()->query('organization_id');

        return $this->success(
            $this->activityLogService->summarize(
                is_numeric($organizationId) ? (int) $organizationId : null,
            ),
        );
    }

    public function indexForOrganization(int $organizationId): JsonResponse
    {
        $organization = Organization::query()->findOrFail($organizationId);
        $logs = $this->activityLogService->paginateForOrganization($organization);

        return $this->success(
            ActivityLogResource::collection($logs->items()),
            [
                'pagination' => [
                    'current_page' => $logs->currentPage(),
                    'per_page' => $logs->perPage(),
                    'total' => $logs->total(),
                    'last_page' => $logs->lastPage(),
                ],
                'summary' => $this->activityLogService->summarize($organization->id),
                'filters' => [
                    'subject_types' => $this->activityLogService->subjectTypeOptions(),
                ],
            ],
        );
    }
}
