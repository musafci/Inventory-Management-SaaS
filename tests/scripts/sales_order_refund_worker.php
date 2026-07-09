#!/usr/bin/env php
<?php

/**
 * Standalone worker for Postgres sales-order refund concurrency tests.
 *
 * Usage:
 *   php tests/scripts/sales_order_refund_worker.php <organization_id> <sales_order_id> <user_id> <amount> <method> <item_id:qty> [<item_id:qty> ...]
 *
 * Exit codes:
 *   0 — refund recorded
 *   3 — validation rejection (422)
 *   2 — unexpected failure
 *   1 — invalid arguments
 */

use App\Enums\PaymentMethod;
use App\Models\Organization;
use App\Models\SalesOrder;
use App\Services\PaymentService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

$app = require $basePath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$organizationId = $argv[1] ?? null;
$salesOrderId = $argv[2] ?? null;
$userId = $argv[3] ?? null;
$amount = $argv[4] ?? null;
$method = $argv[5] ?? null;
$lineSpecs = array_slice($argv, 6);

if (
    ! is_numeric($organizationId)
    || ! is_numeric($salesOrderId)
    || ! is_numeric($userId)
    || ! is_numeric($amount)
    || ! is_string($method)
    || $lineSpecs === []
) {
    fwrite(STDERR, "Invalid arguments.\n");
    exit(1);
}

$returnItems = [];

foreach ($lineSpecs as $index => $lineSpec) {
    if (! str_contains($lineSpec, ':')) {
        fwrite(STDERR, "Invalid line spec at index {$index}.\n");
        exit(1);
    }

    [$salesOrderItemId, $quantity] = explode(':', $lineSpec, 2);

    if (! is_numeric($salesOrderItemId) || ! is_numeric($quantity)) {
        fwrite(STDERR, "Invalid line spec at index {$index}.\n");
        exit(1);
    }

    $returnItems[] = [
        'sales_order_item_id' => (int) $salesOrderItemId,
        'quantity' => (int) $quantity,
    ];
}

$organization = Organization::query()->findOrFail((int) $organizationId);
app()->instance('currentOrganization', $organization);
setPermissionsTeamId($organization->id);

$salesOrder = SalesOrder::query()->findOrFail((int) $salesOrderId);

try {
    app(PaymentService::class)->recordSalesRefund($salesOrder, [
        'amount' => (float) $amount,
        'method' => PaymentMethod::from($method),
        'return_items' => $returnItems,
    ], (int) $userId);
} catch (ValidationException $exception) {
    $request = Request::create('/api/v1/sales-orders/'.$salesOrderId.'/refund', 'POST');
    $response = app(ExceptionHandler::class)->render($request, $exception);

    fwrite(STDOUT, 'status:'.$response->getStatusCode()."\n");

    exit(3);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(2);
}

exit(0);
