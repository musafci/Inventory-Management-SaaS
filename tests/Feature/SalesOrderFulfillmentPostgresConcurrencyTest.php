<?php

/**
 * Postgres-only concurrency tests for sales order fulfillment.
 *
 * Run before signing off Step 12:
 *   RUN_STOCK_PG_CONCURRENCY=1 php artisan test tests/Feature/SalesOrderFulfillmentPostgresConcurrencyTest.php
 */

use App\Enums\SalesOrderStatus;
use App\Models\SalesFulfillment;
use App\Models\SalesOrderItem;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (! filter_var(getenv('RUN_STOCK_PG_CONCURRENCY') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
        test()->markTestSkipped(
            'Set RUN_STOCK_PG_CONCURRENCY=1 to run Postgres sales order fulfillment concurrency verification.',
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

test('postgres parallel full fulfillments on the same confirmed order never double-ship', function () {
    $context = bootstrapSalesConfirmContext();
    $workerScript = base_path('tests/scripts/sales_order_fulfill_worker.php');

    seedSalesConfirmStock($context, 20);

    $quantityPerOrder = 5;
    $salesOrder = createConfirmedSalesOrderForFulfill($context, $quantityPerOrder);
    $lineItemId = $salesOrder->items->first()->id;

    $processA = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrder->id,
        (string) $context['user']->id,
        "{$lineItemId}:{$quantityPerOrder}",
    ], base_path(), stockPostgresWorkerEnvironment());

    $processB = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrder->id,
        (string) $context['user']->id,
        "{$lineItemId}:{$quantityPerOrder}",
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
        ->and(reservedQuantityForProduct($context))->toBe(0)
        ->and(onHandQuantityForProduct($context))->toBe(15)
        ->and(saleOutQuantityForProduct($context))->toBe($quantityPerOrder)
        ->and(SalesFulfillment::query()->count())->toBe(1)
        ->and(SalesOrderItem::query()->findOrFail($lineItemId)->quantity_fulfilled)->toBe($quantityPerOrder)
        ->and(\App\Models\SalesOrder::query()->findOrFail($salesOrder->id)->status)->toBe(SalesOrderStatus::Shipped);
});

test('postgres parallel partial fulfillments on the same confirmed order never over-fulfill', function () {
    $context = bootstrapSalesConfirmContext();
    $workerScript = base_path('tests/scripts/sales_order_fulfill_worker.php');

    seedSalesConfirmStock($context, 20);

    $orderQuantity = 10;
    $attemptQuantity = 6;
    $salesOrder = createConfirmedSalesOrderForFulfill($context, $orderQuantity);
    $lineItemId = $salesOrder->items->first()->id;

    $processA = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrder->id,
        (string) $context['user']->id,
        "{$lineItemId}:{$attemptQuantity}",
    ], base_path(), stockPostgresWorkerEnvironment());

    $processB = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrder->id,
        (string) $context['user']->id,
        "{$lineItemId}:{$attemptQuantity}",
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
        ->and(reservedQuantityForProduct($context))->toBe(4)
        ->and(onHandQuantityForProduct($context))->toBe(14)
        ->and(saleOutQuantityForProduct($context))->toBe($attemptQuantity)
        ->and(SalesFulfillment::query()->count())->toBe(1)
        ->and(SalesOrderItem::query()->findOrFail($lineItemId)->quantity_fulfilled)->toBe($attemptQuantity)
        ->and(\App\Models\SalesOrder::query()->findOrFail($salesOrder->id)->status)->toBe(SalesOrderStatus::Confirmed);
});

test('postgres parallel multi-line fulfillments with reversed product line order complete without deadlock', function () {
    $context = bootstrapOverlappingSalesConfirmContext();
    $workerScript = base_path('tests/scripts/sales_order_fulfill_worker.php');

    seedOverlappingSalesConfirmStock($context, 20);

    $quantityPerLine = 4;

    $salesOrderA = createConfirmedSalesOrderWithProductLineOrder($context, [
        $context['products']['high']->id,
        $context['products']['low']->id,
    ], $quantityPerLine);

    $salesOrderB = createConfirmedSalesOrderWithProductLineOrder($context, [
        $context['products']['low']->id,
        $context['products']['high']->id,
    ], $quantityPerLine);

    $lineSpecsA = $salesOrderA->items
        ->sortByDesc('product_id')
        ->map(fn (SalesOrderItem $item): string => "{$item->id}:{$quantityPerLine}")
        ->values()
        ->all();

    $lineSpecsB = $salesOrderB->items
        ->sortByDesc('product_id')
        ->map(fn (SalesOrderItem $item): string => "{$item->id}:{$quantityPerLine}")
        ->values()
        ->all();

    $processA = new Process(array_merge([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrderA->id,
        (string) $context['user']->id,
    ], $lineSpecsA), base_path(), stockPostgresWorkerEnvironment());

    $processB = new Process(array_merge([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $salesOrderB->id,
        (string) $context['user']->id,
    ], $lineSpecsB), base_path(), stockPostgresWorkerEnvironment());

    $processA->start();
    $processB->start();

    $processA->wait();
    $processB->wait();

    expect($processA->getExitCode())->toBe(0, $processA->getErrorOutput())
        ->and($processB->getExitCode())->toBe(0, $processB->getErrorOutput());

    $expectedReservedPerProduct = 0;
    $expectedOnHandPerProduct = 12;

    expect(reservedQuantityForProductId($context, $context['products']['low']->id))->toBe($expectedReservedPerProduct)
        ->and(reservedQuantityForProductId($context, $context['products']['high']->id))->toBe($expectedReservedPerProduct)
        ->and(\App\Models\Stock::query()
            ->where('warehouse_id', $context['warehouse']->id)
            ->where('product_id', $context['products']['low']->id)
            ->value('quantity_on_hand'))->toBe($expectedOnHandPerProduct)
        ->and(\App\Models\Stock::query()
            ->where('warehouse_id', $context['warehouse']->id)
            ->where('product_id', $context['products']['high']->id)
            ->value('quantity_on_hand'))->toBe($expectedOnHandPerProduct)
        ->and(\App\Models\SalesOrder::query()->where('status', SalesOrderStatus::Shipped)->count())->toBe(2);
});
