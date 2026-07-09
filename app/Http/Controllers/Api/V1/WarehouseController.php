<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Warehouse\StoreWarehouseRequest;
use App\Http\Requests\Warehouse\UpdateWarehouseRequest;
use App\Http\Resources\WarehouseResource;
use App\Models\Warehouse;
use App\Services\WarehouseService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class WarehouseController extends ApiController
{
    public function __construct(
        protected WarehouseService $warehouseService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Warehouse::class);

        $warehouses = $this->warehouseService->paginate();

        return $this->success(
            WarehouseResource::collection($warehouses->items()),
            [
                'pagination' => [
                    'current_page' => $warehouses->currentPage(),
                    'per_page' => $warehouses->perPage(),
                    'total' => $warehouses->total(),
                    'last_page' => $warehouses->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreWarehouseRequest $request): JsonResponse
    {
        $this->authorize('create', Warehouse::class);

        $warehouse = $this->warehouseService->create($request->validated());

        return $this->success(new WarehouseResource($warehouse), status: 201);
    }

    public function show(int $warehouseId): JsonResponse
    {
        $warehouse = $this->findWarehouseForCurrentOrganization($warehouseId);

        $this->authorize('view', $warehouse);

        return $this->success(new WarehouseResource($warehouse));
    }

    public function update(UpdateWarehouseRequest $request, int $warehouseId): JsonResponse
    {
        $warehouse = $this->findWarehouseForCurrentOrganization($warehouseId);

        $this->authorize('update', $warehouse);

        $warehouse = $this->warehouseService->update($warehouse, $request->validated());

        return $this->success(new WarehouseResource($warehouse));
    }

    public function destroy(int $warehouseId): Response
    {
        $warehouse = $this->findWarehouseForCurrentOrganization($warehouseId);

        $this->authorize('delete', $warehouse);

        $this->warehouseService->delete($warehouse);

        return response()->noContent();
    }

    protected function findWarehouseForCurrentOrganization(int $warehouseId): Warehouse
    {
        return Warehouse::query()
            ->whereKey($warehouseId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
