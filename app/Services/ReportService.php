<?php

namespace App\Services;

use App\Enums\PaymentStatus;
use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Models\Payment;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Stock;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    protected int $cacheTtlSeconds = 300;

    protected function cacheKey(string $suffix): string
    {
        $organizationId = app('currentOrganization')->id;

        return "org:{$organizationId}:reports:{$suffix}";
    }

    public function forgetOrganizationCache(?int $organizationId = null): void
    {
        $organizationId ??= app('currentOrganization')->id;

        foreach (['stock-valuation', 'low-stock', 'sales-summary', 'purchase-summary', 'dashboard'] as $suffix) {
            Cache::forget("org:{$organizationId}:reports:{$suffix}");
        }
    }

    /**
     * @return array{
     *     total_products: int,
     *     total_stock_items: int,
     *     stock_value: string,
     *     low_stock_count: int,
     *     pending_purchase_orders: int,
     *     pending_sales_orders: int,
     * }
     */
    public function dashboard(): array
    {
        return Cache::remember(
            $this->cacheKey('dashboard'),
            $this->cacheTtlSeconds,
            function (): array {
                $valuation = $this->stockValuationUncached();
                $lowStock = $this->lowStockUncached();

                $pendingPurchaseOrders = PurchaseOrder::query()
                    ->whereIn('status', [
                        PurchaseOrderStatus::Draft,
                        PurchaseOrderStatus::Sent,
                        PurchaseOrderStatus::PartiallyReceived,
                    ])
                    ->count();

                $pendingSalesOrders = SalesOrder::query()
                    ->whereIn('status', [
                        SalesOrderStatus::Draft,
                        SalesOrderStatus::Confirmed,
                        SalesOrderStatus::Shipped,
                    ])
                    ->count();

                return [
                    'total_products' => \App\Models\Product::query()->count(),
                    'total_stock_items' => Stock::query()->count(),
                    'stock_value' => $valuation['total_value'],
                    'low_stock_count' => count($lowStock),
                    'pending_purchase_orders' => $pendingPurchaseOrders,
                    'pending_sales_orders' => $pendingSalesOrders,
                ];
            },
        );
    }

    /**
     * @return array{
     *     total_value: string,
     *     total_units: int,
     *     by_warehouse: list<array{warehouse_id: int, warehouse_name: string, total_value: string, total_units: int}>,
     * }
     */
    public function stockValuation(?int $warehouseId = null): array
    {
        $suffix = 'stock-valuation'.($warehouseId !== null ? ":{$warehouseId}" : '');

        return Cache::remember(
            $this->cacheKey($suffix),
            $this->cacheTtlSeconds,
            fn (): array => $this->stockValuationUncached($warehouseId),
        );
    }

    /**
     * @return array{
     *     total_value: string,
     *     total_units: int,
     *     by_warehouse: list<array{warehouse_id: int, warehouse_name: string, total_value: string, total_units: int}>,
     * }
     */
    public function stockValuationUncached(?int $warehouseId = null): array
    {
        $query = Stock::query()
            ->join('products', 'products.id', '=', 'stocks.product_id')
            ->join('warehouses', 'warehouses.id', '=', 'stocks.warehouse_id')
            ->select([
                'stocks.warehouse_id',
                'warehouses.name as warehouse_name',
                DB::raw('SUM(stocks.quantity_on_hand * products.cost_price) as total_value'),
                DB::raw('SUM(stocks.quantity_on_hand) as total_units'),
            ])
            ->groupBy('stocks.warehouse_id', 'warehouses.name');

        if ($warehouseId !== null) {
            $query->where('stocks.warehouse_id', $warehouseId);
        }

        $rows = $query->get();

        $byWarehouse = $rows->map(fn ($row): array => [
            'warehouse_id' => (int) $row->warehouse_id,
            'warehouse_name' => $row->warehouse_name,
            'total_value' => number_format((float) $row->total_value, 2, '.', ''),
            'total_units' => (int) $row->total_units,
        ])->values()->all();

        $totalValue = array_reduce(
            $byWarehouse,
            fn (float $carry, array $row): float => $carry + (float) $row['total_value'],
            0.0,
        );

        $totalUnits = array_reduce(
            $byWarehouse,
            fn (int $carry, array $row): int => $carry + $row['total_units'],
            0,
        );

        return [
            'total_value' => number_format($totalValue, 2, '.', ''),
            'total_units' => $totalUnits,
            'valuation_basis' => 'quantity_on_hand',
            'by_warehouse' => $byWarehouse,
        ];
    }

    /**
     * @return list<array{
     *     stock_id: int,
     *     warehouse_id: int,
     *     warehouse_name: string,
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     quantity_on_hand: int,
     *     quantity_reserved: int,
     *     quantity_available: int,
     *     reorder_point: int,
     * }>
     */
    public function lowStock(): array
    {
        return Cache::remember(
            $this->cacheKey('low-stock'),
            $this->cacheTtlSeconds,
            fn (): array => $this->lowStockUncached(),
        );
    }

    /**
     * @return list<array{
     *     stock_id: int,
     *     warehouse_id: int,
     *     warehouse_name: string,
     *     product_id: int,
     *     product_name: string,
     *     sku: string,
     *     quantity_on_hand: int,
     *     quantity_reserved: int,
     *     quantity_available: int,
     *     reorder_point: int,
     * }>
     */
    public function lowStockUncached(): array
    {
        return Stock::query()
            ->with(['warehouse', 'product'])
            ->whereHas('product', function ($query): void {
                $query->whereNotNull('reorder_point')
                    ->whereColumn('products.reorder_point', '>=', DB::raw('(stocks.quantity_on_hand - stocks.quantity_reserved)'));
            })
            ->orderBy('warehouse_id')
            ->orderBy('product_id')
            ->get()
            ->map(fn (Stock $stock): array => [
                'stock_id' => $stock->id,
                'warehouse_id' => $stock->warehouse_id,
                'warehouse_name' => $stock->warehouse->name,
                'product_id' => $stock->product_id,
                'product_name' => $stock->product->name,
                'sku' => $stock->product->sku,
                'quantity_on_hand' => $stock->quantity_on_hand,
                'quantity_reserved' => $stock->quantity_reserved,
                'quantity_available' => $stock->quantity_available,
                'reorder_point' => (int) $stock->product->reorder_point,
            ])
            ->values()
            ->all();
    }

    /**
     * @return array{
     *     filters: array{
     *         order_date: array{from: string|null, to: string|null},
     *         payment_date: array{from: string|null, to: string|null},
     *     },
     *     order_count: int,
     *     total_amount: string,
     *     by_status: list<array{status: string, order_count: int, total_amount: string}>,
     *     payments_received: string,
     * }
     */
    public function salesSummary(
        ?string $orderFrom = null,
        ?string $orderTo = null,
        ?string $paymentFrom = null,
        ?string $paymentTo = null,
    ): array {
        $suffix = md5(json_encode([$orderFrom, $orderTo, $paymentFrom, $paymentTo]) ?: '');

        return Cache::remember(
            $this->cacheKey("sales-summary:{$suffix}"),
            $this->cacheTtlSeconds,
            fn (): array => $this->salesSummaryUncached($orderFrom, $orderTo, $paymentFrom, $paymentTo),
        );
    }

    /**
     * @return array{
     *     filters: array{
     *         order_date: array{from: string|null, to: string|null},
     *         payment_date: array{from: string|null, to: string|null},
     *     },
     *     order_count: int,
     *     total_amount: string,
     *     by_status: list<array{status: string, order_count: int, total_amount: string}>,
     *     payments_received: string,
     * }
     */
    public function salesSummaryUncached(
        ?string $orderFrom = null,
        ?string $orderTo = null,
        ?string $paymentFrom = null,
        ?string $paymentTo = null,
    ): array {
        $ordersQuery = SalesOrder::query();

        if ($orderFrom !== null) {
            $ordersQuery->whereDate('order_date', '>=', $orderFrom);
        }

        if ($orderTo !== null) {
            $ordersQuery->whereDate('order_date', '<=', $orderTo);
        }

        $statusRows = (clone $ordersQuery)
            ->select([
                'status',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_amount'),
            ])
            ->groupBy('status')
            ->get();

        $byStatus = $statusRows->map(fn ($row): array => [
            'status' => $row->status instanceof SalesOrderStatus ? $row->status->value : (string) $row->status,
            'order_count' => (int) $row->order_count,
            'total_amount' => number_format((float) $row->total_amount, 2, '.', ''),
        ])->values()->all();

        $orderCount = (int) (clone $ordersQuery)->count();
        $totalAmount = (float) (clone $ordersQuery)->sum('total_amount');

        $paymentsReceived = null;

        if ($paymentFrom !== null || $paymentTo !== null) {
            $paymentsQuery = Payment::query()
                ->where('payable_type', SalesOrder::class)
                ->where('status', PaymentStatus::Completed);

            if ($paymentFrom !== null) {
                $paymentsQuery->whereDate('paid_at', '>=', $paymentFrom);
            }

            if ($paymentTo !== null) {
                $paymentsQuery->whereDate('paid_at', '<=', $paymentTo);
            }

            $paymentsReceived = (float) $paymentsQuery->sum('amount');

            $refundsQuery = Payment::query()
                ->where('payable_type', SalesOrder::class)
                ->where('status', PaymentStatus::Refunded);

            if ($paymentFrom !== null) {
                $refundsQuery->whereDate('paid_at', '>=', $paymentFrom);
            }

            if ($paymentTo !== null) {
                $refundsQuery->whereDate('paid_at', '<=', $paymentTo);
            }

            $paymentsReceived -= (float) $refundsQuery->sum('amount');
            $paymentsReceived = number_format(max(0, $paymentsReceived), 2, '.', '');
        }

        return [
            'filters' => [
                'order_date' => ['from' => $orderFrom, 'to' => $orderTo],
                'payment_date' => ['from' => $paymentFrom, 'to' => $paymentTo],
            ],
            'order_count' => $orderCount,
            'total_amount' => number_format($totalAmount, 2, '.', ''),
            'by_status' => $byStatus,
            'payments_received' => $paymentsReceived,
        ];
    }

    /**
     * @return array{
     *     filters: array{
     *         order_date: array{from: string|null, to: string|null},
     *         payment_date: array{from: string|null, to: string|null},
     *     },
     *     order_count: int,
     *     total_amount: string,
     *     by_status: list<array{status: string, order_count: int, total_amount: string}>,
     *     payments_made: string,
     * }
     */
    public function purchaseSummary(
        ?string $orderFrom = null,
        ?string $orderTo = null,
        ?string $paymentFrom = null,
        ?string $paymentTo = null,
    ): array {
        $suffix = md5(json_encode([$orderFrom, $orderTo, $paymentFrom, $paymentTo]) ?: '');

        return Cache::remember(
            $this->cacheKey("purchase-summary:{$suffix}"),
            $this->cacheTtlSeconds,
            fn (): array => $this->purchaseSummaryUncached($orderFrom, $orderTo, $paymentFrom, $paymentTo),
        );
    }

    /**
     * @return array{
     *     filters: array{
     *         order_date: array{from: string|null, to: string|null},
     *         payment_date: array{from: string|null, to: string|null},
     *     },
     *     order_count: int,
     *     total_amount: string,
     *     by_status: list<array{status: string, order_count: int, total_amount: string}>,
     *     payments_made: string,
     * }
     */
    public function purchaseSummaryUncached(
        ?string $orderFrom = null,
        ?string $orderTo = null,
        ?string $paymentFrom = null,
        ?string $paymentTo = null,
    ): array {
        $ordersQuery = PurchaseOrder::query();

        if ($orderFrom !== null) {
            $ordersQuery->whereDate('order_date', '>=', $orderFrom);
        }

        if ($orderTo !== null) {
            $ordersQuery->whereDate('order_date', '<=', $orderTo);
        }

        $statusRows = (clone $ordersQuery)
            ->select([
                'status',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_amount'),
            ])
            ->groupBy('status')
            ->get();

        $byStatus = $statusRows->map(fn ($row): array => [
            'status' => $row->status instanceof PurchaseOrderStatus ? $row->status->value : (string) $row->status,
            'order_count' => (int) $row->order_count,
            'total_amount' => number_format((float) $row->total_amount, 2, '.', ''),
        ])->values()->all();

        $orderCount = (int) (clone $ordersQuery)->count();
        $totalAmount = (float) (clone $ordersQuery)->sum('total_amount');

        $paymentsMade = null;

        if ($paymentFrom !== null || $paymentTo !== null) {
            $paymentsQuery = Payment::query()
                ->where('payable_type', PurchaseOrder::class)
                ->where('status', PaymentStatus::Completed);

            if ($paymentFrom !== null) {
                $paymentsQuery->whereDate('paid_at', '>=', $paymentFrom);
            }

            if ($paymentTo !== null) {
                $paymentsQuery->whereDate('paid_at', '<=', $paymentTo);
            }

            $paymentsMade = number_format((float) $paymentsQuery->sum('amount'), 2, '.', '');
        }

        return [
            'filters' => [
                'order_date' => ['from' => $orderFrom, 'to' => $orderTo],
                'payment_date' => ['from' => $paymentFrom, 'to' => $paymentTo],
            ],
            'order_count' => $orderCount,
            'total_amount' => number_format($totalAmount, 2, '.', ''),
            'by_status' => $byStatus,
            'payments_made' => $paymentsMade,
        ];
    }
}
