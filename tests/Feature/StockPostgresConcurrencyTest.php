<?php

/**
 * Postgres-only concurrency tests for stock locking.
 *
 * Run manually once before signing off Step 6:
 *   RUN_STOCK_PG_CONCURRENCY=1 php artisan test tests/Feature/StockPostgresConcurrencyTest.php
 *
 * Requires a reachable Postgres instance (defaults to 127.0.0.1:5433 per docker-compose).
 */

use App\Models\Stock;
use App\Models\StockMovement;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (! filter_var(getenv('RUN_STOCK_PG_CONCURRENCY') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
        test()->markTestSkipped(
            'Set RUN_STOCK_PG_CONCURRENCY=1 to run Postgres stock concurrency verification.',
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

test('postgres parallel workers on a brand new stock pair both succeed with correct final balance', function () {
    $context = bootstrapStockContext();

    expect(Stock::query()->count())->toBe(0);

    $workerScript = base_path('tests/scripts/stock_concurrency_worker.php');
    $workers = 2;
    $quantityPerWorker = 3;
    $processes = [];

    for ($worker = 0; $worker < $workers; $worker++) {
        $process = new Process([
            PHP_BINARY,
            $workerScript,
            (string) $context['organization']->id,
            (string) $context['warehouse']->id,
            (string) $context['product']->id,
            (string) $context['user']->id,
            (string) $quantityPerWorker,
        ], base_path(), stockPostgresWorkerEnvironment());

        $process->start();
        $processes[] = $process;
    }

    foreach ($processes as $process) {
        $process->wait();
        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
    }

    $expectedQuantity = $workers * $quantityPerWorker;

    expect(Stock::query()->count())->toBe(1)
        ->and(currentQuantityOnHand($context))->toBe($expectedQuantity)
        ->and(StockMovement::query()->count())->toBe($workers);
});

test('postgres parallel workers increment existing stock without lost updates', function () {
    $context = bootstrapStockContext();
    $workerScript = base_path('tests/scripts/stock_concurrency_worker.php');

    app(\App\Services\StockService::class)->recordMovement(movementPayload($context, [
        'type' => \App\Enums\StockMovementType::AdjustmentIn,
        'quantity' => 10,
    ]));

    $workers = 8;
    $quantityPerWorker = 2;
    $processes = [];

    for ($worker = 0; $worker < $workers; $worker++) {
        $process = new Process([
            PHP_BINARY,
            $workerScript,
            (string) $context['organization']->id,
            (string) $context['warehouse']->id,
            (string) $context['product']->id,
            (string) $context['user']->id,
            (string) $quantityPerWorker,
        ], base_path(), stockPostgresWorkerEnvironment());

        $process->start();
        $processes[] = $process;
    }

    foreach ($processes as $process) {
        $process->wait();
        expect($process->getExitCode())->toBe(0, $process->getErrorOutput());
    }

    expect(currentQuantityOnHand($context))->toBe(10 + ($workers * $quantityPerWorker))
        ->and(StockMovement::query()->count())->toBe(1 + $workers);
});

test('postgres parallel withdrawals serialize oversell attempts with clean rejections', function () {
    $context = bootstrapStockContext();
    $workerScript = base_path('tests/scripts/stock_concurrency_worker.php');

    app(\App\Services\StockService::class)->recordMovement(movementPayload($context, [
        'type' => \App\Enums\StockMovementType::AdjustmentIn,
        'quantity' => 10,
    ]));

    $workers = 6;
    $quantityPerWorker = 3;
    $processes = [];
    $successes = 0;
    $rejections = 0;

    for ($worker = 0; $worker < $workers; $worker++) {
        $process = new Process([
            PHP_BINARY,
            $workerScript,
            (string) $context['organization']->id,
            (string) $context['warehouse']->id,
            (string) $context['product']->id,
            (string) $context['user']->id,
            (string) $quantityPerWorker,
            \App\Enums\StockMovementType::AdjustmentOut->value,
        ], base_path(), stockPostgresWorkerEnvironment());

        $process->start();
        $processes[] = $process;
    }

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

    $successfulWithdrawalQuantity = $successes * $quantityPerWorker;

    expect($successes)->toBe(3)
        ->and($rejections)->toBe(3)
        ->and($successfulWithdrawalQuantity)->toBeLessThanOrEqual(10)
        ->and(currentQuantityOnHand($context))->toBe(10 - $successfulWithdrawalQuantity)
        ->and(currentQuantityOnHand($context))->toBeGreaterThanOrEqual(0)
        ->and(StockMovement::query()->where('type', \App\Enums\StockMovementType::AdjustmentOut)->count())->toBe($successes)
        ->and(StockMovement::query()->count())->toBe(1 + $successes);
});
