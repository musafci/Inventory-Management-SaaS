#!/usr/bin/env php
<?php

/**
 * Standalone worker for Postgres purchase-order number concurrency tests.
 *
 * Usage:
 *   php tests/scripts/purchase_order_concurrency_worker.php <organization_id> <supplier_id> <warehouse_id> <product_id> <user_id>
 *
 * Exit codes:
 *   0 — purchase order created
 *   3 — validation rejection
 *   2 — unexpected failure
 *   1 — invalid arguments
 */

use App\Models\Organization;
use App\Services\PurchaseOrderService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

$app = require $basePath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$organizationId, $supplierId, $warehouseId, $productId, $userId] = [
    $argv[1] ?? null,
    $argv[2] ?? null,
    $argv[3] ?? null,
    $argv[4] ?? null,
    $argv[5] ?? null,
];

if (! is_numeric($organizationId) || ! is_numeric($supplierId) || ! is_numeric($warehouseId) || ! is_numeric($productId) || ! is_numeric($userId)) {
    fwrite(STDERR, "Invalid arguments.\n");
    exit(1);
}

$organization = Organization::query()->findOrFail((int) $organizationId);
app()->instance('currentOrganization', $organization);
setPermissionsTeamId($organization->id);

try {
    $purchaseOrder = app(PurchaseOrderService::class)->create([
        'supplier_id' => (int) $supplierId,
        'warehouse_id' => (int) $warehouseId,
        'order_date' => '2026-07-09',
        'items' => [
            [
                'product_id' => (int) $productId,
                'quantity_ordered' => 5,
                'unit_cost' => 10,
            ],
        ],
    ]);

    fwrite(STDOUT, 'po_number:'.$purchaseOrder->po_number."\n");
} catch (ValidationException $exception) {
    $request = Request::create('/api/v1/purchase-orders', 'POST');
    $response = app(ExceptionHandler::class)->render($request, $exception);

    fwrite(STDOUT, 'status:'.$response->getStatusCode()."\n");

    exit(3);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(2);
}

exit(0);
