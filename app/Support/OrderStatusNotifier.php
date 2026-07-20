<?php

namespace App\Support;

use App\Enums\PurchaseOrderStatus;
use App\Enums\SalesOrderStatus;
use App\Jobs\SendOrderStatusNotificationJob;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;

class OrderStatusNotifier
{
    public static function purchaseOrderChanged(
        PurchaseOrder $purchaseOrder,
        PurchaseOrderStatus $previousStatus,
    ): void {
        $newStatus = $purchaseOrder->status;

        if ($previousStatus === $newStatus) {
            return;
        }

        SendOrderStatusNotificationJob::dispatch(
            organizationId: $purchaseOrder->organization_id,
            orderType: 'purchase_order',
            orderId: $purchaseOrder->id,
            orderNumber: $purchaseOrder->po_number,
            previousStatus: $previousStatus->value,
            newStatus: $newStatus->value,
        );
    }

    public static function salesOrderChanged(
        SalesOrder $salesOrder,
        SalesOrderStatus $previousStatus,
    ): void {
        $newStatus = $salesOrder->status;

        if ($previousStatus === $newStatus) {
            return;
        }

        SendOrderStatusNotificationJob::dispatch(
            organizationId: $salesOrder->organization_id,
            orderType: 'sales_order',
            orderId: $salesOrder->id,
            orderNumber: $salesOrder->order_number,
            previousStatus: $previousStatus->value,
            newStatus: $newStatus->value,
        );
    }
}
