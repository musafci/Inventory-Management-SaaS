#!/usr/bin/env php
<?php

/**
 * Standalone worker for Postgres sales-order idempotency concurrency tests.
 *
 * Usage:
 *   php tests/scripts/sales_order_create_idempotency_worker.php <organization_id> <customer_id> <warehouse_id> <product_id> <user_id> <idempotency_key>
 *
 * Exit codes:
 *   0 — sales order created or replayed
 *   3 — validation rejection (422)
 *   2 — unexpected failure
 *   1 — invalid arguments
 */

use App\Models\Organization;
use App\Services\IdempotencyService;
use App\Services\SalesOrderService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

$app = require $basePath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

[$organizationId, $customerId, $warehouseId, $productId, $userId, $idempotencyKey] = [
    $argv[1] ?? null,
    $argv[2] ?? null,
    $argv[3] ?? null,
    $argv[4] ?? null,
    $argv[5] ?? null,
    $argv[6] ?? null,
];

if (
    ! is_numeric($organizationId)
    || ! is_numeric($customerId)
    || ! is_numeric($warehouseId)
    || ! is_numeric($productId)
    || ! is_numeric($userId)
    || ! is_string($idempotencyKey)
    || $idempotencyKey === ''
) {
    fwrite(STDERR, "Invalid arguments.\n");
    exit(1);
}

$organization = Organization::query()->findOrFail((int) $organizationId);
app()->instance('currentOrganization', $organization);
setPermissionsTeamId($organization->id);

$payload = [
    'customer_id' => (int) $customerId,
    'warehouse_id' => (int) $warehouseId,
    'order_date' => '2026-07-09',
    'items' => [
        [
            'product_id' => (int) $productId,
            'quantity' => 3,
            'unit_price' => 15,
            'discount' => 0,
        ],
    ],
];

$routeFingerprint = IdempotencyService::routeFingerprint('POST', 'api/v1/sales-orders');
$requestHash = IdempotencyService::fingerprintRequest('POST', 'api/v1/sales-orders', $payload);

$idempotencyService = app(IdempotencyService::class);

try {
    $begin = $idempotencyService->begin(
        (int) $organizationId,
        (int) $userId,
        $idempotencyKey,
        $routeFingerprint,
        $requestHash,
    );

    if ($begin->isReplay) {
        $decoded = json_decode((string) $begin->responseBody, true);
        $orderNumber = $decoded['data']['order_number'] ?? null;
        $salesOrderId = $decoded['data']['id'] ?? null;

        fwrite(STDOUT, 'replayed:1'."\n");
        fwrite(STDOUT, 'status:'.$begin->responseStatusCode."\n");
        fwrite(STDOUT, 'order_number:'.$orderNumber."\n");
        fwrite(STDOUT, 'sales_order_id:'.$salesOrderId."\n");

        exit(0);
    }

    $salesOrder = app(SalesOrderService::class)->create($payload);

    $responseBody = json_encode([
        'data' => [
            'id' => $salesOrder->id,
            'order_number' => $salesOrder->order_number,
            'status' => $salesOrder->status->value,
            'total_amount' => $salesOrder->total_amount,
        ],
    ], JSON_THROW_ON_ERROR);

    $response = response($responseBody, 201)->header('Content-Type', 'application/json');
    $idempotencyService->complete($begin->record, $response);

    fwrite(STDOUT, 'replayed:0'."\n");
    fwrite(STDOUT, 'status:201'."\n");
    fwrite(STDOUT, 'order_number:'.$salesOrder->order_number."\n");
    fwrite(STDOUT, 'sales_order_id:'.$salesOrder->id."\n");
} catch (ValidationException $exception) {
    if (isset($begin) && $begin->record !== null && ! $begin->isReplay) {
        $idempotencyService->release($begin->record);
    }

    $request = Request::create('/api/v1/sales-orders', 'POST');
    $response = app(ExceptionHandler::class)->render($request, $exception);

    fwrite(STDOUT, 'status:'.$response->getStatusCode()."\n");

    exit(3);
} catch (Throwable $exception) {
    if (isset($begin) && $begin->record !== null && ! $begin->isReplay) {
        $idempotencyService->release($begin->record);
    }

    fwrite(STDERR, $exception->getMessage()."\n");
    exit(2);
}

exit(0);
