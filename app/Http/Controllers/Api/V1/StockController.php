<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\StockResource;
use App\Models\Stock;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;

class StockController extends ApiController
{
    public function __construct(
        protected StockService $stockService,
    ) {}

    public function index(): JsonResponse
    {
        $this->authorize('viewAny', Stock::class);

        $stocks = $this->stockService->paginateStocks();

        return $this->success(
            StockResource::collection($stocks->items()),
            [
                'pagination' => [
                    'current_page' => $stocks->currentPage(),
                    'per_page' => $stocks->perPage(),
                    'total' => $stocks->total(),
                    'last_page' => $stocks->lastPage(),
                ],
            ],
        );
    }
}
