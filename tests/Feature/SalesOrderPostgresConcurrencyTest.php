<?php

/**
 * Postgres-only concurrency tests for sales order confirmation.
 *
 * Run before signing off Step 11:
 *   RUN_STOCK_PG_CONCURRENCY=1 php artisan test tests/Feature/SalesOrderPostgresConcurrencyTest.php
 */

use App\Enums\SalesOrderStatus;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (! filter_var(getenv('RUN_STOCK_PG_CONCURRENCY') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
        test()->markTestSkipped(
            'Set RUN_STOCK_PG_CONCURRENCY=1 to run Postgres sales order concurrency verification.',
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

test('postgres parallel confirmations on different orders serialize oversell attempts', function () {
    $context = bootstrapSalesConfirmContext();
    $workerScript = base_path('tests/scripts/sales_order_confirm_worker.php');

    seedSalesConfirmStock($context, 10);

    $quantityPerOrder = 3;
    $workers = 4;
    $salesOrderIds = [];

    for ($index = 0; $index < $workers; $index++) {
        $salesOrderIds[] = createDraftSalesOrderForConfirm($context, $quantityPerOrder)->id;
    }

    $processes = [];

    foreach ($salesOrderIds as $salesOrderId) {
        $process = new Process([
            PHP_BINARY,
            $workerScript,
            (string) $context['organization']->id,
            (string) $salesOrderId,
        ], base_path(), stockPostgresWorkerEnvironment());

        $process->start();
        $processes[] = $process;
    }

    $successes = 0;
    $rejections = 0;

    foreach ($processes as $process) {
        $process->wait();

        if ($process->getExitCode() === 0) {
            $successes++;
            continue;
        }

        if ($process->getExitCode() === 3) {
            expect($process->getOutput())->toContain('status:422');
            $rejections++;

            continue;
        }

        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
    }

    expect($successes)->toBe(3)
        ->and($rejections)->toBe(1)
        ->and(reservedQuantityForProduct($context))->toBe(9)
        ->and(reservedQuantityForProduct($context))->toBeLessThanOrEqual(onHandQuantityForProduct($context))
        ->and(\App\Models\SalesOrder::query()->where('status', SalesOrderStatus::Confirmed)->count())->toBe(3)
        ->and(\App\Models\SalesOrder::query()->where('status', SalesOrderStatus::Draft)->count())->toBe(1);
});

test('postgres parallel confirmations on the same draft order never double-reserve', function () {
    $context = bootstrapSalesConfirmContext();
    $workerScript = base_path('tests/scripts/sales_order_confirm_worker.php');

    seedSalesConfirmStock($context, 20);

    $quantityPerOrder = 3;
    $salesOrderId = createDraftSalesOrderForConfirm($context, $quantityPerOrder)->id;

    $processA = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrderId,
    ], base_path(), stockPostgresWorkerEnvironment());

    $processB = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrderId,
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
        ->and(reservedQuantityForProduct($context))->toBe($quantityPerOrder)
        ->and(\App\Models\SalesOrder::query()->findOrFail($salesOrderId)->status)->toBe(SalesOrderStatus::Confirmed);
});

test('postgres parallel multi-line confirmations with reversed product line order complete without deadlock', function () {
    $context = bootstrapOverlappingSalesConfirmContext();
    $workerScript = base_path('tests/scripts/sales_order_confirm_worker.php');

    seedOverlappingSalesConfirmStock($context, 20);

    $quantityPerLine = 4;

    $salesOrderA = createDraftSalesOrderWithProductLineOrder($context, [
        $context['products']['high']->id,
        $context['products']['low']->id,
    ], $quantityPerLine);

    $salesOrderB = createDraftSalesOrderWithProductLineOrder($context, [
        $context['products']['low']->id,
        $context['products']['high']->id,
    ], $quantityPerLine);

    $processA = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrderA->id,
    ], base_path(), stockPostgresWorkerEnvironment());

    $processB = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrderB->id,
    ], base_path(), stockPostgresWorkerEnvironment());

    $processA->start();
    $processB->start();

    $processA->wait();
    $processB->wait();

    expect($processA->getExitCode())->toBe(0, $processA->getErrorOutput())
        ->and($processB->getExitCode())->toBe(0, $processB->getErrorOutput());

    $expectedReservedPerProduct = $quantityPerLine * 2;

    expect(reservedQuantityForProductId($context, $context['products']['low']->id))->toBe($expectedReservedPerProduct)
        ->and(reservedQuantityForProductId($context, $context['products']['high']->id))->toBe($expectedReservedPerProduct)
        ->and(\App\Models\SalesOrder::query()->where('status', SalesOrderStatus::Confirmed)->count())->toBe(2);
});
