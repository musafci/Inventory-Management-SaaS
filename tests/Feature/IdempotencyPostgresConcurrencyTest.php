<?php

/**
 * Postgres-only concurrency tests for idempotency-key store races.
 *
 * Run manually before signing off Step 15:
 *   RUN_STOCK_PG_CONCURRENCY=1 php artisan test tests/Feature/IdempotencyPostgresConcurrencyTest.php
 */

use App\Models\SalesOrder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;

beforeEach(function (): void {
    if (! filter_var(getenv('RUN_STOCK_PG_CONCURRENCY') ?: '0', FILTER_VALIDATE_BOOLEAN)) {
        test()->markTestSkipped(
            'Set RUN_STOCK_PG_CONCURRENCY=1 to run Postgres idempotency concurrency verification.',
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

test('postgres parallel sales order creates with the same idempotency key create one order and replay', function () {
    $context = bootstrapSalesConfirmContext();
    $workerScript = base_path('tests/scripts/sales_order_create_idempotency_worker.php');
    $idempotencyKey = 'parallel-so-'.fake()->uuid();

    $processA = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $context['customer']->id,
        (string) $context['warehouse']->id,
        (string) $context['product']->id,
        (string) $context['user']->id,
        $idempotencyKey,
    ], base_path(), stockPostgresWorkerEnvironment());

    $processB = new Process([
        PHP_BINARY,
        $workerScript,
        (string) $context['organization']->id,
        (string) $context['customer']->id,
        (string) $context['warehouse']->id,
        (string) $context['product']->id,
        (string) $context['user']->id,
        $idempotencyKey,
    ], base_path(), stockPostgresWorkerEnvironment());

    $processA->start();
    $processB->start();

    $processA->wait();
    $processB->wait();

    expect($processA->getExitCode())->toBe(0, $processA->getErrorOutput())
        ->and($processB->getExitCode())->toBe(0, $processB->getErrorOutput());

    preg_match('/order_number:(SO-\d+)/', $processA->getOutput(), $matchesA);
    preg_match('/order_number:(SO-\d+)/', $processB->getOutput(), $matchesB);
    preg_match('/sales_order_id:(\d+)/', $processA->getOutput(), $idMatchesA);
    preg_match('/sales_order_id:(\d+)/', $processB->getOutput(), $idMatchesB);

    expect($matchesA[1] ?? null)->not->toBeNull()
        ->and($matchesB[1] ?? null)->toBe($matchesA[1])
        ->and($idMatchesA[1] ?? null)->toBe($idMatchesB[1] ?? null)
        ->and(SalesOrder::query()->count())->toBe(1);

    $replayedFlags = [];

    foreach ([$processA->getOutput(), $processB->getOutput()] as $output) {
        preg_match('/replayed:([01])/', $output, $replayedMatch);
        $replayedFlags[] = $replayedMatch[1] ?? null;
    }

    expect($replayedFlags)->toContain('0')
        ->and($replayedFlags)->toContain('1');
});
