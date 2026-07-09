<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\StockMovement\StoreStockMovementRequest;
use App\Http\Resources\StockMovementResource;
use App\Models\StockMovement;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;

class StockMovementController extends ApiController
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', StockMovement::class);

        $movements = $this->stockService->paginateMovements();

        return $this->success(
            StockMovementResource::collection($movements->items()),
            [
                'pagination' => [
                    'current_page' => $movements->currentPage(),
                    'per_page' => $movements->perPage(),
                    'total' => $movements->total(),
                    'last_page' => $movements->lastPage(),
                ],
            ],
        );
    }

    public function store(StoreStockMovementRequest $request): JsonResponse
    {
        $this->authorize('create', StockMovement::class);

        $movement = $this->stockService->recordMovement([
            ...$request->validated(),
            'created_by' => $request->user()->id,
        ]);

        return $this->success(new StockMovementResource($movement), status: 201);
    }
}
