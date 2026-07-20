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
        $this->authorize('viewReports');

        return $this->success($this->reportService->dashboard());
    }

    public function stockValuation(Request $request): JsonResponse
    {
        $this->authorize('viewReports');

        $warehouseId = $request->filled('warehouse_id')
            ? $request->integer('warehouse_id')
            : null;

        return $this->success($this->reportService->stockValuation($warehouseId));
    }

    public function lowStock(): JsonResponse
    {
        $this->authorize('viewReports');

        return $this->success($this->reportService->lowStock());
    }

    public function salesSummary(Request $request): JsonResponse
    {
        $this->authorize('viewReports');

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
        $this->authorize('viewReports');

        [$orderFrom, $orderTo, $paymentFrom, $paymentTo] = $this->resolveSummaryDateFilters($request);

        return $this->success($this->reportService->purchaseSummary(
            $orderFrom,
            $orderTo,
            $paymentFrom,
            $paymentTo,
        ));
    }

    /**
     * Order metrics filter on order_date; payment metrics filter on paid_at.
     *
     * Legacy `from`/`to` apply to order_date only. Use payment_from/payment_to
     * (or payment_from/payment_to aliases) for cash-basis payment totals.
     *
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
