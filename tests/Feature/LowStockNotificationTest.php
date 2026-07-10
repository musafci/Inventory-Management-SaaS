<?php

use App\Enums\StockMovementType;
use App\Notifications\LowStockNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setUpPassport();
});

/**
 * @return array{warehouse_id: int, product_id: int}
 */
function createLowStockCatalog(object $test, array $headers, int $reorderPoint = 10): array
{
    $category = $test->postJson('/api/v1/categories', ['name' => 'Low Stock Cat'], $headers)->assertCreated();
    $unit = $test->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)->assertCreated();
    $product = $test->postJson('/api/v1/products', [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Low Stock Item',
        'sku' => 'LOW-'.fake()->unique()->numerify('####'),
        'cost_price' => 5,
        'selling_price' => 10,
        'reorder_point' => $reorderPoint,
    ], $headers)->assertCreated();

    $test->postJson('/api/v1/warehouses', ['name' => 'Alert Warehouse'], $headers)->assertCreated();
    $warehouseId = $test->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    return [
        'warehouse_id' => $warehouseId,
        'product_id' => $product->json('data.id'),
    ];
}

test('stock movement dropping quantity below reorder point creates exactly one notification', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'low-stock@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createLowStockCatalog($this, $headers, reorderPoint: 10);

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 15,
    ], $headers)->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentOut->value,
        'quantity' => 6,
    ], $headers)->assertCreated();

    expect(DB::table('notifications')->count())->toBe(1);

    $notification = DB::table('notifications')->first();
    expect($notification->type)->toBe(LowStockNotification::class)
        ->and(json_decode($notification->data, true)['product_id'])->toBe($catalog['product_id'])
        ->and(json_decode($notification->data, true)['quantity_on_hand'])->toBe(9)
        ->and(json_decode($notification->data, true)['reorder_point'])->toBe(10);
});

test('a second low stock movement within 24 hours does not create a duplicate notification', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'low-stock-dedup@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createLowStockCatalog($this, $headers, reorderPoint: 10);

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 15,
    ], $headers)->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentOut->value,
        'quantity' => 6,
    ], $headers)->assertCreated();

    expect(DB::table('notifications')->count())->toBe(1);

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentOut->value,
        'quantity' => 2,
    ], $headers)->assertCreated();

    expect(DB::table('notifications')->count())->toBe(1);
});

test('stock movement that stays above reorder point does not create a notification', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'low-stock-above@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createLowStockCatalog($this, $headers, reorderPoint: 10);

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 20,
    ], $headers)->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentOut->value,
        'quantity' => 5,
    ], $headers)->assertCreated();

    expect(DB::table('notifications')->count())->toBe(0);
});
