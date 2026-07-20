<?php

namespace App\Events;

use App\Enums\PurchaseOrderStatus;
use App\Models\PurchaseOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PurchaseOrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public PurchaseOrder $purchaseOrder,
        public PurchaseOrderStatus $previousStatus,
        public PurchaseOrderStatus $newStatus,
    ) {}
}
