<?php

use App\Enums\StockMovementType;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

/**
 * @return array{category_id: int, unit_id: int, product_id: int}
 */
function createStockCatalog(object $test, array $headers): array
{
    $category = $test->postJson('/api/v1/categories', ['name' => 'Stock Cat'], $headers)->assertCreated();
    $unit = $test->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)->assertCreated();
    $product = $test->postJson('/api/v1/products', [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Tracked Item',
        'sku' => 'STK-'.fake()->unique()->numerify('####'),
        'cost_price' => 5,
        'selling_price' => 10,
    ], $headers)->assertCreated();

    $test->postJson('/api/v1/warehouses', ['name' => 'Main Warehouse'], $headers)->assertCreated();

    return [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'product_id' => $product->json('data.id'),
    ];
}

test('stock movement store records an adjustment_in movement', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);

    $warehouse = $this->getJson('/api/v1/warehouses', $headers)->assertOk();

    $response = $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouse->json('data.0.id'),
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 15,
        'note' => 'Initial stock take',
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.type', 'adjustment_in')
        ->assertJsonPath('data.quantity', 15);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 15)
        ->assertJsonPath('data.0.quantity_available', 15);
});

test('stock movement store records an adjustment_out movement', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-out@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 20,
    ], $headers)->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentOut->value,
        'quantity' => 7,
        'note' => 'Damaged units',
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.type', 'adjustment_out');

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 13);
});

test('stock movement store returns 422 when withdrawal exceeds on hand and leaves no partial writes', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-insufficient@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 5,
    ], $headers)->assertCreated();

    $response = $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentOut->value,
        'quantity' => 8,
    ], $headers);

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'The given data was invalid.')
        ->assertJsonValidationErrors(['quantity']);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 5);

    expect(StockMovement::query()->count())->toBe(1);
});

test('stock movement store rejects non manual movement types', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-type@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::PurchaseIn->value,
        'quantity' => 5,
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['type']);
});

test('stock index lists current on hand levels', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-index@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 9,
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/stocks', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 9)
        ->assertJsonStructure([
            'data' => [[
                'id', 'warehouse_id', 'product_id', 'quantity_on_hand',
                'quantity_reserved', 'quantity_available', 'organization_id',
            ]],
            'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']],
        ]);
});

test('stock index supports filter by warehouse_id', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-wh-filter@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);

    $mainWarehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');
    $secondWarehouse = $this->postJson('/api/v1/warehouses', ['name' => 'Overflow'], $headers)->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $mainWarehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 5,
    ], $headers)->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $secondWarehouse->json('data.id'),
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 9,
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/stocks?filter[warehouse_id]='.$mainWarehouseId, $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity_on_hand', 5);
});

test('stock index supports low_stock filter', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-low@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $this->putJson('/api/v1/products/'.$catalog['product_id'], [
        'reorder_point' => 10,
    ], $headers)->assertOk();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 8,
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/stocks?filter[low_stock]=true', $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity_on_hand', 8);
});

test('stock movement index returns ledger history', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-ledger@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 4,
        'note' => 'Counted',
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/stock-movements', $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'adjustment_in')
        ->assertJsonPath('data.0.note', 'Counted');
});

test('stock movement index supports filters by warehouse product and type', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-mv-filter@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 10,
    ], $headers)->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentOut->value,
        'quantity' => 2,
    ], $headers)->assertCreated();

    $response = $this->getJson(
        '/api/v1/stock-movements?filter[warehouse_id]='.$warehouseId
        .'&filter[product_id]='.$catalog['product_id']
        .'&filter[type]=adjustment_out',
        $headers,
    );

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.type', 'adjustment_out');
});

test('viewer cannot record a stock movement', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stk-viewer-owner@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createStockCatalog($this, $headers);
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $viewer = User::factory()->create(['email' => 'stk-viewer@acme.test']);
    $viewer->organizations()->attach($org['organization_id'], ['role' => 'Viewer']);
    setPermissionsTeamId($org['organization_id']);
    $viewer->assignRole('Viewer');

    $viewerLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'stk-viewer@acme.test',
        'password' => 'password',
    ])->assertOk();

    $viewerHeaders = $this->organizationContextHeaders(
        $viewerLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 1,
    ], $viewerHeaders)->assertForbidden();
});

test('stock index never leaks records from another organization', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'stk-leak-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'stk-leak-b@acme.test']);

    $headersA = $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']);
    $catalogA = createStockCatalog($this, $headersA);
    $warehouseA = $this->getJson('/api/v1/warehouses', $headersA)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseA,
        'product_id' => $catalogA['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 6,
    ], $headersA)->assertCreated();

    $categoryB = \App\Models\Category::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Cat',
        'slug' => 'org-b-cat',
    ]);
    $unitB = \App\Models\Unit::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Piece',
        'symbol' => 'p2',
    ]);
    $warehouseB = \App\Models\Warehouse::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Warehouse',
        'is_default' => true,
    ]);
    $productB = \App\Models\Product::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'category_id' => $categoryB->id,
        'unit_id' => $unitB->id,
        'name' => 'Org B Product',
        'sku' => 'ORG-B-1',
        'cost_price' => 1,
        'selling_price' => 2,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    Stock::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'warehouse_id' => $warehouseB->id,
        'product_id' => $productB->id,
        'quantity_on_hand' => 50,
        'quantity_reserved' => 0,
    ]);

    $this->getJson('/api/v1/stocks', $headersA)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity_on_hand', 6);
});

test('stock movement ledger never leaks records from another organization', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'stk-mv-leak-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'stk-mv-leak-b@acme.test']);

    $headersA = $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']);
    $catalogA = createStockCatalog($this, $headersA);
    $warehouseA = $this->getJson('/api/v1/warehouses', $headersA)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseA,
        'product_id' => $catalogA['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 3,
    ], $headersA)->assertCreated();

    $userB = User::factory()->create();
    $categoryB = \App\Models\Category::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Cat',
        'slug' => 'org-b-cat-mv',
    ]);
    $unitB = \App\Models\Unit::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Piece',
        'symbol' => 'p3',
    ]);
    $warehouseB = \App\Models\Warehouse::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Warehouse',
        'is_default' => true,
    ]);
    $productB = \App\Models\Product::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'category_id' => $categoryB->id,
        'unit_id' => $unitB->id,
        'name' => 'Org B Product',
        'sku' => 'ORG-B-2',
        'cost_price' => 1,
        'selling_price' => 2,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    \Illuminate\Support\Facades\DB::table('stock_movements')->insert([
        'organization_id' => $orgB['organization_id'],
        'warehouse_id' => $warehouseB->id,
        'product_id' => $productB->id,
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 99,
        'created_by' => $userB->id,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->getJson('/api/v1/stock-movements', $headersA)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity', 3);
});
