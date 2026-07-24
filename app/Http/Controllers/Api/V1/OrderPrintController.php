<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Resources\PurchaseOrderResource;
use App\Http\Resources\SalesOrderResource;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use Illuminate\Http\Response;

class OrderPrintController extends ApiController
{
    public function salesOrder(int $salesOrderId): Response
    {
        $salesOrder = SalesOrder::query()
            ->whereKey($salesOrderId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();

        $this->authorize('view', $salesOrder);

        $salesOrder->load(['customer', 'warehouse', 'items.product']);
        /** @var \App\Models\Organization $organization */
        $organization = app('currentOrganization');

        return response()->view('print.sales-order', [
            'order' => (new SalesOrderResource($salesOrder))->resolve(),
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'autoPrint' => false,
        ])->header('Content-Type', 'text/html; charset=UTF-8');
    }

    public function purchaseOrder(int $purchaseOrderId): Response
    {
        $purchaseOrder = PurchaseOrder::query()
            ->whereKey($purchaseOrderId)
            ->where('organization_id', app('currentOrganization')->id)
            ->firstOrFail();

        $this->authorize('view', $purchaseOrder);

        $purchaseOrder->load(['supplier', 'warehouse', 'items.product']);
        /** @var \App\Models\Organization $organization */
        $organization = app('currentOrganization');

        return response()->view('print.purchase-order', [
            'order' => (new PurchaseOrderResource($purchaseOrder))->resolve(),
            'organization' => [
                'id' => $organization->id,
                'name' => $organization->name,
            ],
            'autoPrint' => false,
        ])->header('Content-Type', 'text/html; charset=UTF-8');
    }
}
