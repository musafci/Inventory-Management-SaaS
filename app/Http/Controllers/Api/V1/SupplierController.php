<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Supplier\StoreSupplierRequest;
use App\Http\Requests\Supplier\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use App\Services\SupplierService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;

class SupplierController extends ApiController
{
    public function __construct(
        protected SupplierService $supplierService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Supplier::class);

        $suppliers = $this->supplierService->paginate();

        return $this->success(
            SupplierResource::collection($suppliers->items()),
            [
                'pagination' => [
                    'current_page' => $suppliers->currentPage(),
                    'per_page' => $suppliers->perPage(),
                    'total' => $suppliers->total(),
                    'last_page' => $suppliers->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreSupplierRequest $request): JsonResponse
    {
        $this->authorize('create', Supplier::class);

        $supplier = $this->supplierService->create($request->validated());

        return $this->success(new SupplierResource($supplier), status: 201);
    }

    public function show(int $supplierId): JsonResponse
    {
        $supplier = $this->findSupplierForCurrentOrganization($supplierId);

        $this->authorize('view', $supplier);

        return $this->success(new SupplierResource($supplier));
    }

    public function update(UpdateSupplierRequest $request, int $supplierId): JsonResponse
    {
        $supplier = $this->findSupplierForCurrentOrganization($supplierId);

        $this->authorize('update', $supplier);

        $supplier = $this->supplierService->update($supplier, $request->validated());

        return $this->success(new SupplierResource($supplier));
    }

    public function destroy(int $supplierId): Response
    {
        $supplier = $this->findSupplierForCurrentOrganization($supplierId);

        $this->authorize('delete', $supplier);

        $this->supplierService->delete($supplier);

        return response()->noContent();
    }

    protected function findSupplierForCurrentOrganization(int $supplierId): Supplier
    {
        return Supplier::query()
            ->whereKey($supplierId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();
    }
}
