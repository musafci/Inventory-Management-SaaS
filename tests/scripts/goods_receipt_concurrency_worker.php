#!/usr/bin/env php
<?php

/**
 * Standalone worker for Postgres goods-receipt concurrency tests.
 *
 * Usage:
 *   php tests/scripts/goods_receipt_concurrency_worker.php <organization_id> <purchase_order_id> <user_id> <item_id:qty> [<item_id:qty> ...]
 *
 * Line items are processed in the order given on the command line (payload order).
 * GoodsReceiptService must re-sort by product_id before locking stock rows.
 *
 * Exit codes:
 *   0 — receipt recorded
 *   3 — validation rejection (422)
 *   2 — unexpected failure
 *   1 — invalid arguments
 */

use App\Models\Organization;
use App\Models\PurchaseOrder;
use App\Services\GoodsReceiptService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

$app = require $basePath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$organizationId = $argv[1] ?? null;
$purchaseOrderId = $argv[2] ?? null;
$userId = $argv[3] ?? null;
$lineSpecs = array_slice($argv, 4);

if (! is_numeric($organizationId) || ! is_numeric($purchaseOrderId) || ! is_numeric($userId) || $lineSpecs === []) {
    fwrite(STDERR, "Invalid arguments.\n");
    exit(1);
}

$items = [];

foreach ($lineSpecs as $index => $lineSpec) {
    if (! str_contains($lineSpec, ':')) {
        fwrite(STDERR, "Invalid line spec at index {$index}.\n");
        exit(1);
    }

    [$purchaseOrderItemId, $quantity] = explode(':', $lineSpec, 2);

    if (! is_numeric($purchaseOrderItemId) || ! is_numeric($quantity)) {
        fwrite(STDERR, "Invalid line spec at index {$index}.\n");
        exit(1);
    }

    $items[] = [
        'purchase_order_item_id' => (int) $purchaseOrderItemId,
        'quantity' => (int) $quantity,
    ];
}

$organization = Organization::query()->findOrFail((int) $organizationId);
app()->instance('currentOrganization', $organization);
setPermissionsTeamId($organization->id);

$purchaseOrder = PurchaseOrder::query()->findOrFail((int) $purchaseOrderId);

try {
    app(GoodsReceiptService::class)->receive($purchaseOrder, ['items' => $items], (int) $userId);
} catch (ValidationException $exception) {
    $request = Request::create('/api/v1/purchase-orders/'.$purchaseOrderId.'/receive', 'POST');
    $response = app(ExceptionHandler::class)->render($request, $exception);

    fwrite(STDOUT, 'status:'.$response->getStatusCode()."\n");

    exit(3);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(2);
}

exit(0);
