#!/usr/bin/env php
<?php

/**
 * Standalone worker for Postgres sales-order confirm concurrency tests.
 *
 * Usage: php tests/scripts/sales_order_confirm_worker.php <organization_id> <sales_order_id>
 *
 * Exit codes:
 *   0 — order confirmed
 *   3 — validation rejection (422)
 *   2 — unexpected failure
 *   1 — invalid arguments
 */

use App\Models\Organization;
use App\Models\SalesOrder;
use App\Services\SalesOrderService;
use Illuminate\Contracts\Debug\ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

$basePath = dirname(__DIR__, 2);

require $basePath.'/vendor/autoload.php';

$app = require $basePath.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$organizationId = $argv[1] ?? null;
$salesOrderId = $argv[2] ?? null;

if (! is_numeric($organizationId) || ! is_numeric($salesOrderId)) {
    fwrite(STDERR, "Invalid arguments.\n");
    exit(1);
}

$organization = Organization::query()->findOrFail((int) $organizationId);
app()->instance('currentOrganization', $organization);
setPermissionsTeamId($organization->id);

$salesOrder = SalesOrder::query()->findOrFail((int) $salesOrderId);

try {
    app(SalesOrderService::class)->confirm($salesOrder);
} catch (ValidationException $exception) {
    $request = Request::create('/api/v1/sales-orders/'.$salesOrderId.'/confirm', 'POST');
    $response = app(ExceptionHandler::class)->render($request, $exception);

    fwrite(STDOUT, 'status:'.$response->getStatusCode()."\n");

    exit(3);
} catch (Throwable $exception) {
    fwrite(STDERR, $exception->getMessage()."\n");
    exit(2);
}

exit(0);
