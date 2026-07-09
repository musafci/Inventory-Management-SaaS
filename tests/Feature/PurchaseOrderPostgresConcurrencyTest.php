<?php

/**
 * Postgres-only concurrency tests for goods receipt locking.
 *
 * Run manually before signing off Purchasing:
 *   RUN_STOCK_PG_CONCURRENCY=1 php artisan test tests/Feature/PurchaseOrderPostgresConcurrencyTest.php
 */

use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (! filter_var(getenv('RUN_STOCK_PG_CONCURRENCY') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
        test()->markTestSkipped(
            'Set RUN_STOCK_PG_CONCURRENCY=1 to run Postgres goods receipt concurrency verification.',
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

test('postgres parallel goods receipts with reversed line order complete without deadlock', function () {
    $context = bootstrapOverlappingReceiptContext();
    $workerScript = base_path('tests/scripts/goods_receipt_concurrency_worker.php');

    $purchaseOrderA = createSentPurchaseOrderWithProducts($context);
    $purchaseOrderB = createSentPurchaseOrderWithProducts($context);

    $lowItemA = $purchaseOrderA->items->firstWhere('product_id', $context['products']['low']->id);
    $highItemA = $purchaseOrderA->items->firstWhere('product_id', $context['products']['high']->id);
    $lowItemB = $purchaseOrderB->items->firstWhere('product_id', $context['products']['low']->id);
    $highItemB = $purchaseOrderB->items->firstWhere('product_id', $context['products']['high']->id);

    $quantityPerLine = 4;

    $processA = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $purchaseOrderA->id,
        (string) $context['user']->id,
        "{$highItemA->id}:{$quantityPerLine}",
        "{$lowItemA->id}:{$quantityPerLine}",
    ], base_path(), stockPostgresWorkerEnvironment());

    $processB = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $purchaseOrderB->id,
        (string) $context['user']->id,
        "{$lowItemB->id}:{$quantityPerLine}",
        "{$highItemB->id}:{$quantityPerLine}",
    ], base_path(), stockPostgresWorkerEnvironment());

    $processA->start();
    $processB->start();

    $processA->wait();
    $processB->wait();

    expect($processA->getExitCode())->toBe(0, $processA->getErrorOutput())
        ->and($processB->getExitCode())->toBe(0, $processB->getErrorOutput());

    $expectedPerProduct = $quantityPerLine * 2;

    expect(stockQuantityForProduct($context, $context['products']['low']->id))->toBe($expectedPerProduct)
        ->and(stockQuantityForProduct($context, $context['products']['high']->id))->toBe($expectedPerProduct)
        ->and(GoodsReceipt::query()->count())->toBe(2)
        ->and(StockMovement::query()->where('type', StockMovementType::PurchaseIn)->count())->toBe(4);
});

test('postgres parallel purchase order creation assigns unique po numbers without failures', function () {
    $context = bootstrapOverlappingReceiptContext();
    $workerScript = base_path('tests/scripts/purchase_order_concurrency_worker.php');
    $workers = 8;
    $processes = [];
    $poNumbers = [];

    for ($worker = 0; $worker < $workers; $worker++) {
        $process = new Process([
            PHP_BINARY,
            $workerScript,
            (string) $context['organization']->id,
            (string) $context['supplier']->id,
            (string) $context['warehouse']->id,
            (string) $context['products']['low']->id,
            (string) $context['user']->id,
        ], base_path(), stockPostgresWorkerEnvironment());

        $process->start();
        $processes[] = $process;
    }

    foreach ($processes as $process) {
        $process->wait();
        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());

        preg_match('/po_number:(PO-\d+)/', $process->getOutput(), $matches);
        expect($matches[1] ?? null)->not->toBeNull();
        $poNumbers[] = $matches[1];
    }

    expect($poNumbers)->toHaveCount($workers)
        ->and(array_unique($poNumbers))->toHaveCount($workers)
        ->and(\App\Models\PurchaseOrder::query()->count())->toBe($workers);
});
