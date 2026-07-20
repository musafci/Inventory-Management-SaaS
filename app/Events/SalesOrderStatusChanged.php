<?php

namespace App\Events;

use App\Enums\SalesOrderStatus;
use App\Models\SalesOrder;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SalesOrderStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public SalesOrder $salesOrder,
        public SalesOrderStatus $previousStatus,
        public SalesOrderStatus $newStatus,
    ) {}
}
