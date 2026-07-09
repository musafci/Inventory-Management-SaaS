<?php

use App\Enums\StockMovementType;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Warehouse;
use App\Services\StockService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

test('direct stock quantity_on_hand updates are forbidden outside the movement observer', function () {
    $context = bootstrapStockContext();

    $stock = Stock::withoutOrganizationScope()->create([
        'organization_id' => $context['organization']->id,
        'warehouse_id' => $context['warehouse']->id,
        'product_id' => $context['product']->id,
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
    ]);

    expect(fn () => $stock->update(['quantity_on_hand' => 20]))
        ->toThrow(RuntimeException::class, 'stocks.quantity_on_hand must NEVER be updated directly');
});

test('recordMovement updates quantity_on_hand only through stock_movements inserts', function () {
    $context = bootstrapStockContext();
    $service = app(StockService::class);

    $service->recordMovement(movementPayload($context, [
        'type' => StockMovementType::PurchaseIn,
        'quantity' => 12,
    ]));

    $service->recordMovement(movementPayload($context, [
        'type' => StockMovementType::SaleOut,
        'quantity' => 4,
    ]));

    expect(currentQuantityOnHand($context))->toBe(8);
});

test('lockStockRow does not create duplicate rows when called twice for the same pair', function () {
    $context = bootstrapStockContext();
    $service = app(StockService::class);

    $method = new ReflectionMethod($service, 'lockStockRow');
    $method->setAccessible(true);

    $first = $method->invoke($service, $context['warehouse']->id, $context['product']->id);
    $second = $method->invoke($service, $context['warehouse']->id, $context['product']->id);

    expect($first->id)->toBe($second->id)
        ->and(Stock::query()->count())->toBe(1);
});

test('recordMovement succeeds when a competing request already created the stock row', function () {
    $context = bootstrapStockContext();

    Stock::withoutOrganizationScope()->create([
        'organization_id' => $context['organization']->id,
        'warehouse_id' => $context['warehouse']->id,
        'product_id' => $context['product']->id,
        'quantity_on_hand' => 0,
        'quantity_reserved' => 0,
    ]);

    app(StockService::class)->recordMovement(movementPayload($context, [
        'type' => StockMovementType::AdjustmentIn,
        'quantity' => 9,
    ]));

    expect(Stock::query()->count())->toBe(1)
        ->and(currentQuantityOnHand($context))->toBe(9);
});

test('many sequential movements on sqlite prove ledger math under serialized execution', function () {
    $context = bootstrapStockContext();
    $service = app(StockService::class);

    foreach (range(1, 100) as $ignored) {
        $service->recordMovement(movementPayload($context, [
            'type' => StockMovementType::PurchaseIn,
            'quantity' => 1,
        ]));
    }

    expect(currentQuantityOnHand($context))->toBe(100)
        ->and(Stock::query()->count())->toBe(1);
});

test('rapid interleaved inbound and outbound movements keep the correct running balance', function () {
    $context = bootstrapStockContext();
    $service = app(StockService::class);

    foreach (range(1, 50) as $cycle) {
        $service->recordMovement(movementPayload($context, [
            'type' => StockMovementType::AdjustmentIn,
            'quantity' => 3,
        ]));

        $service->recordMovement(movementPayload($context, [
            'type' => StockMovementType::AdjustmentOut,
            'quantity' => 1,
        ]));
    }

    expect(currentQuantityOnHand($context))->toBe(100)
        ->and(StockMovement::query()->count())->toBe(100);
});

test('nested transactions still commit a consistent final stock quantity', function () {
    $context = bootstrapStockContext();
    $service = app(StockService::class);

    DB::transaction(function () use ($service, $context): void {
        foreach (range(1, 10) as $ignored) {
            $service->recordMovement(movementPayload($context, [
                'type' => StockMovementType::PurchaseIn,
                'quantity' => 4,
            ]));
        }
    });

    expect(currentQuantityOnHand($context))->toBe(40);
});

test('outbound movement rejects quantities that would make stock negative', function () {
    $context = bootstrapStockContext();
    $service = app(StockService::class);

    $service->recordMovement(movementPayload($context, [
        'type' => StockMovementType::PurchaseIn,
        'quantity' => 5,
    ]));

    expect(fn () => $service->recordMovement(movementPayload($context, [
        'type' => StockMovementType::SaleOut,
        'quantity' => 8,
    ])))->toThrow(Illuminate\Validation\ValidationException::class);

    expect(currentQuantityOnHand($context))->toBe(5);
});

/**
 * @return array{0: Illuminate\Database\QueryException}
 */
function makeQueryExceptionWithErrorInfo(array $errorInfo): Illuminate\Database\QueryException
{
    $pdoException = new PDOException('driver error', (int) ($errorInfo[1] ?? 0));
    $pdoException->errorInfo = $errorInfo;

    return new Illuminate\Database\QueryException('testing', 'insert into stocks', [], $pdoException);
}

test('isUniqueConstraintViolation recognizes postgres sqlstate 23505', function () {
    $exception = makeQueryExceptionWithErrorInfo([
        '23505',
        7,
        'ERROR: duplicate key value violates unique constraint "stocks_organization_id_warehouse_id_product_id_unique"',
    ]);

    expect(\App\Support\UniqueConstraintViolation::matches($exception))->toBeTrue();
});

test('releaseReservation clamps over-release without driving reserved quantity negative', function () {
    $context = bootstrapStockContext();
    $service = app(StockService::class);

    $service->recordMovement(movementPayload($context, [
        'type' => StockMovementType::PurchaseIn,
        'quantity' => 10,
    ]));

    $stock = Stock::query()->firstOrFail();

    Stock::$quantityReservedUpdateFromService = true;

    try {
        $stock->quantity_reserved = 3;
        $stock->save();
    } finally {
        Stock::$quantityReservedUpdateFromService = false;
    }

    $service->releaseReservation(
        $context['warehouse']->id,
        $context['product']->id,
        5,
    );

    expect((int) $stock->fresh()->quantity_reserved)->toBe(0);
});

test('isUniqueConstraintViolation recognizes sqlite unique constraint driver code 19', function () {
    $exception = makeQueryExceptionWithErrorInfo([
        '23000',
        19,
        'UNIQUE constraint failed: stocks.organization_id, stocks.warehouse_id, stocks.product_id',
    ]);

    expect(\App\Support\UniqueConstraintViolation::matches($exception))->toBeTrue();
});

test('isUniqueConstraintViolation ignores non unique sqlite constraint violations', function () {
    $exception = makeQueryExceptionWithErrorInfo([
        '23000',
        19,
        'FOREIGN KEY constraint failed',
    ]);

    expect(\App\Support\UniqueConstraintViolation::matches($exception))->toBeFalse();
});

test('recordTransfer writes paired transfer_out and transfer_in movements', function () {
    $context = bootstrapStockContext();
    $service = app(StockService::class);

    $destination = Warehouse::withoutOrganizationScope()->create([
        'organization_id' => $context['organization']->id,
        'name' => 'Secondary Warehouse',
        'is_default' => false,
    ]);

    $service->recordMovement(movementPayload($context, [
        'type' => StockMovementType::PurchaseIn,
        'quantity' => 20,
    ]));

    $service->recordTransfer([
        'from_warehouse_id' => $context['warehouse']->id,
        'to_warehouse_id' => $destination->id,
        'product_id' => $context['product']->id,
        'quantity' => 6,
        'created_by' => $context['user']->id,
    ]);

    expect(currentQuantityOnHand($context))->toBe(14)
        ->and((int) Stock::query()->where('warehouse_id', $destination->id)->value('quantity_on_hand'))->toBe(6)
        ->and(StockMovement::query()->where('type', StockMovementType::TransferOut)->count())->toBe(1)
        ->and(StockMovement::query()->where('type', StockMovementType::TransferIn)->count())->toBe(1);
});
