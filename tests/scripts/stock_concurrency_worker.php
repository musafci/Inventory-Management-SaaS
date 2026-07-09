#!/usr/bin/env php
<?php

/**
 * Standalone worker for Postgres stock concurrency tests.
 *
 * Usage:
 *   php tests/scripts/stock_concurrency_worker.php <organization_id> <warehouse_id> <product_id> <user_id> <quantity> [movement_type]
 *
 * movement_type defaults to adjustment_in. Use adjustment_out for withdrawals.
 *
 * Exit codes:
 *   0 — movement recorded
 *   3 — validation rejection (same ValidationException the API renders as 422)
 *   2 — unexpected failure
 *   1 — invalid arguments
 */

use App\Enums\StockMovementType;
use App\Models\Organization;
use App\Services\StockService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

$app = require $basePath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$organizationId = $argv[1] ?? null;
$warehouseId = $argv[2] ?? null;
$productId = $argv[3] ?? null;
$userId = $argv[4] ?? null;
$quantity = $argv[5] ?? null;
$movementType = $argv[6] ?? StockMovementType::AdjustmentIn->value;

if (! is_numeric($organizationId) || ! is_numeric($warehouseId) || ! is_numeric($productId) || ! is_numeric($userId) || ! is_numeric($quantity)) {
    fwrite(STDERR, "Invalid arguments.\n");
    exit(1);
}

try {
    $type = StockMovementType::from($movementType);
} catch (Throwable) {
    fwrite(STDERR, "Invalid movement type.\n");
    exit(1);
}

$organization = Organization::query()->findOrFail((int) $organizationId);
app()->instance('currentOrganization', $organization);
setPermissionsTeamId($organization->id);

try {
    app(StockService::class)->recordMovement([
        'warehouse_id' => (int) $warehouseId,
        'product_id' => (int) $productId,
        'type' => $type,
        'quantity' => (int) $quantity,
        'created_by' => (int) $userId,
    ]);
} catch (ValidationException $exception) {
    $request = Request::create('/api/v1/stock-movements', 'POST');
    $response = app(ExceptionHandler::class)->render($request, $exception);

    fwrite(STDOUT, 'status:'.$response->getStatusCode()."\n");

    exit(3);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(2);
}

exit(0);
