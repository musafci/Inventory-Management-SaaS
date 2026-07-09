<?php

/**
 * Postgres-only concurrency tests for sales order refunds with stock returns.
 *
 * Run before signing off refund+return:
 *   RUN_STOCK_PG_CONCURRENCY=1 php artisan test tests/Feature/SalesOrderRefundPostgresConcurrencyTest.php
 */

use App\Enums\PaymentStatus;
use App\Enums\SalesOrderStatus;
use App\Enums\StockMovementType;
use App\Models\Payment;
use App\Models\SalesOrderItem;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (! filter_var(getenv('RUN_STOCK_PG_CONCURRENCY') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
        test()->markTestSkipped(
            'Set RUN_STOCK_PG_CONCURRENCY=1 to run Postgres sales order refund concurrency verification.',
        );
    }

    $env = stockPostgresWorkerEnvironment();

    config([
        'database.default' => 'pgsql',
        'database.connections.pgsql' => [
            'driver' => 'pgsql',
            'host' => $env['DB_HOST'],
            'port' => $env['DB_PORT'],
            'database' => $env['DB_DATABASE'],
            'username' => $env['DB_USERNAME'],
            'password' => $env['DB_PASSWORD'],
            'charset' => 'utf8',
            'prefix' => '',
            'prefix_indexes' => true,
            'search_path' => 'public',
            'sslmode' => 'prefer',
        ],
    ]);

    DB::purge('pgsql');
    DB::reconnect('pgsql');

    Artisan::call('migrate:fresh', ['--force' => true]);
});

test('postgres parallel refunds with identical return_items on the same order never double-credit stock', function () {
    $context = bootstrapSalesConfirmContext();
    $workerScript = base_path('tests/scripts/sales_order_refund_worker.php');

    seedSalesConfirmStock($context, 50);

    $quantity = 5;
    $refundContext = createShippedAndPaidSalesOrderForRefund($context, $quantity);
    $salesOrderId = $refundContext['sales_order']->id;
    $lineItemId = $refundContext['line_item_id'];
    $refundAmount = $refundContext['refund_amount'];

    expect(onHandQuantityForProduct($context))->toBe(45)
        ->and(saleOutQuantityForProduct($context))->toBe($quantity);

    $processA = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrderId,
        (string) $context['user']->id,
        $refundAmount,
        'cash',
        "{$lineItemId}:{$quantity}",
    ], base_path(), stockPostgresWorkerEnvironment());

    $processB = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrderId,
        (string) $context['user']->id,
        $refundAmount,
        'cash',
        "{$lineItemId}:{$quantity}",
    ], base_path(), stockPostgresWorkerEnvironment());

    $processA->start();
    $processB->start();

    $processA->wait();
    $processB->wait();

    $exitCodes = [$processA->getExitCode(), $processB->getExitCode()];
    sort($exitCodes);

    expect($exitCodes)->toBe([0, 3])
        ->and(collect([$processA, $processB])->first(fn (Process $process): bool => $process->getExitCode() === 3)?->getOutput())
        ->toContain('status:422')
        ->and(returnInQuantityForProduct($context))->toBe($quantity)
        ->and(onHandQuantityForProduct($context))->toBe(50)
        ->and(Payment::query()->where('status', PaymentStatus::Refunded)->count())->toBe(1)
        ->and(StockMovement::query()->where('type', StockMovementType::ReturnIn)->count())->toBe(1)
        ->and(SalesOrderItem::query()->findOrFail($lineItemId)->quantity_returned)->toBe($quantity)
        ->and(\App\Models\SalesOrder::query()->findOrFail($salesOrderId)->status)->toBe(SalesOrderStatus::Refunded);
});
