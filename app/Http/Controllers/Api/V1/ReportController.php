<?php

namespace App\Http\Controllers\Api\V1;

use App\Services\ReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ReportController extends ApiController
{
    public function __construct(
        protected ReportService $reportService,
    ) {}

    public function dashboard(): JsonResponse
    {
        $this->authorize('viewAnyDashboard');

        return $this->success($this->reportService->dashboard());
    }

    public function stockValuation(Request $request): JsonResponse
    {
        $this->authorize('viewInventoryReports');

        $warehouseId = $request->filled('warehouse_id')
            ? $request->integer('warehouse_id')
            : null;

        return $this->success($this->reportService->stockValuation($warehouseId));
    }

    public function lowStock(Request $request): JsonResponse
    {
        $this->authorize('viewInventoryReports');

        $warehouseId = $request->filled('warehouse_id')
            ? $request->integer('warehouse_id')
            : null;

        return $this->success($this->reportService->lowStock($warehouseId));
    }

    public function salesSummary(Request $request): JsonResponse
    {
        $this->authorize('viewSalesReports');

        [$orderFrom, $orderTo, $paymentFrom, $paymentTo] = $this->resolveSummaryDateFilters($request);

        return $this->success($this->reportService->salesSummary(
            $orderFrom,
            $orderTo,
            $paymentFrom,
            $paymentTo,
        ));
    }

    public function purchaseSummary(Request $request): JsonResponse
    {
        $this->authorize('viewPurchaseReports');

        [$orderFrom, $orderTo, $paymentFrom, $paymentTo] = $this->resolveSummaryDateFilters($request);

        return $this->success($this->reportService->purchaseSummary(
            $orderFrom,
            $orderTo,
            $paymentFrom,
            $paymentTo,
        ));
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string, 3: ?string}
     */
    protected function resolveSummaryDateFilters(Request $request): array
    {
        $orderFrom = $request->query('order_from', $request->query('from'));
        $orderTo = $request->query('order_to', $request->query('to'));
        $paymentFrom = $request->query('payment_from');
        $paymentTo = $request->query('payment_to');

        return [$orderFrom, $orderTo, $paymentFrom, $paymentTo];
    }
}
