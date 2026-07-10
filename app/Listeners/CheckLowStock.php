<?php

namespace App\Listeners;

use App\Events\StockLevelChanged;
use App\Jobs\SendLowStockNotificationJob;

class CheckLowStock
{
    public function handle(StockLevelChanged $event): void
    {
        $reorderPoint = $event->product->reorder_point;

        if ($reorderPoint === null) {
            return;
        }

        if ($event->newQuantityOnHand > $reorderPoint) {
            return;
        }

        SendLowStockNotificationJob::dispatch(
            organizationId: $event->stock->organization_id,
            stockId: $event->stock->id,
            productId: $event->product->id,
            warehouseId: $event->stock->warehouse_id,
            quantityOnHand: $event->newQuantityOnHand,
            reorderPoint: $reorderPoint,
        );
    }
}
