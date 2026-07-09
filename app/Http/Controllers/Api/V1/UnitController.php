<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Unit\StoreUnitRequest;
use App\Http\Requests\Unit\UpdateUnitRequest;
use App\Http\Resources\UnitResource;
use App\Models\Unit;
use App\Services\UnitService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class UnitController extends ApiController
{
    public function __construct(
        protected UnitService $unitService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Unit::class);

        $units = $this->unitService->paginate();

        return $this->success(
            UnitResource::collection($units->items()),
            [
                'pagination' => [
                    'current_page' => $units->currentPage(),
                    'per_page' => $units->perPage(),
                    'total' => $units->total(),
                    'last_page' => $units->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreUnitRequest $request): JsonResponse
    {
        $this->authorize('create', Unit::class);

        $unit = $this->unitService->create($request->validated());

        return $this->success(new UnitResource($unit), status: 201);
    }

    public function show(int $unitId): JsonResponse
    {
        $unit = $this->findUnitForCurrentOrganization($unitId);

        $this->authorize('view', $unit);

        return $this->success(new UnitResource($unit));
    }

    public function update(UpdateUnitRequest $request, int $unitId): JsonResponse
    {
        $unit = $this->findUnitForCurrentOrganization($unitId);

        $this->authorize('update', $unit);

        $unit = $this->unitService->update($unit, $request->validated());

        return $this->success(new UnitResource($unit));
    }

    public function destroy(int $unitId): Response
    {
        $unit = $this->findUnitForCurrentOrganization($unitId);

        $this->authorize('delete', $unit);

        $this->unitService->delete($unit);

        return response()->noContent();
    }

    protected function findUnitForCurrentOrganization(int $unitId): Unit
    {
        return Unit::query()
            ->whereKey($unitId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
