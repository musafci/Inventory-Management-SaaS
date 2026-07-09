<?php

use App\Enums\StockMovementType;
use App\Models\SalesFulfillment;
use App\Models\SalesFulfillmentItem;
use App\Models\SalesOrderItem;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('sales order store creates a draft sales order with line items', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);

    $response = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            [
                'product_id' => $catalog['product_id'],
                'quantity' => 3,
                'unit_price' => 15,
                'discount' => 0,
            ],
        ],
    ], withIdempotencyKey($headers));

    $response->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.order_number', 'SO-000001')
        ->assertJsonPath('data.total_amount', '45.00');
});

test('sales order confirm reserves stock under lock without writing sale_out yet', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-confirm@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 20);

    $salesOrderId = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 7, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated()->json('data.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed');

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 20)
        ->assertJsonPath('data.0.quantity_reserved', 7)
        ->assertJsonPath('data.0.quantity_available', 13);

    expect(StockMovement::query()->where('type', StockMovementType::SaleOut)->count())->toBe(0);
});

test('sales order confirm returns 422 when available stock is insufficient', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-insufficient@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 5);

    $salesOrderId = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 8, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated()->json('data.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['quantity']);

    $stock = Stock::query()->first();
    expect($stock->quantity_on_hand)->toBe(5)
        ->and($stock->quantity_reserved)->toBe(0);

    $this->getJson("/api/v1/sales-orders/{$salesOrderId}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'draft');
});

test('sales order cancel releases reservations from a confirmed order', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-cancel@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 12);

    $salesOrderId = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 4, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated()->json('data.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/cancel", [], $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_reserved', 0)
        ->assertJsonPath('data.0.quantity_available', 12);
});

test('sales orders are isolated between organizations', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'so-tenant-a@acme.test']);
    $headersA = $this->organizationHeaders($orgA['token'], $orgA['organization_id']);
    $catalogA = createSalesCatalog($this, $headersA);

    $salesOrderId = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalogA['customer_id'],
        'warehouse_id' => $catalogA['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalogA['product_id'], 'quantity' => 1, 'unit_price' => 10],
        ],
    ], withIdempotencyKey($headersA))->assertCreated()->json('data.id');

    $orgB = $this->registerOrganizationWithOwner(['email' => 'so-tenant-b@acme.test']);
    $headersB = $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']);

    $this->getJson("/api/v1/sales-orders/{$salesOrderId}", $headersB)->assertNotFound();
});

test('sales order partial fulfill releases reservation and writes sale_out without shipping', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-partial-fulfill@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 20);

    $salesOrder = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 10, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $salesOrderId = $salesOrder->json('data.id');
    $lineItemId = $salesOrder->json('data.items.0.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/fulfill", [
        'items' => [['sales_order_item_id' => $lineItemId, 'quantity' => 4]],
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.items.0.quantity_fulfilled', 4);

    $this->getJson("/api/v1/sales-orders/{$salesOrderId}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.items.0.quantity_fulfilled', 4);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 16)
        ->assertJsonPath('data.0.quantity_reserved', 6)
        ->assertJsonPath('data.0.quantity_available', 10);

    expect(StockMovement::query()->where('type', StockMovementType::SaleOut)->sum('quantity'))->toBe(4);
});

test('sales order full fulfill transitions to shipped and clears reservation', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-full-fulfill@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 12);

    $salesOrder = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 5, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $salesOrderId = $salesOrder->json('data.id');
    $lineItemId = $salesOrder->json('data.items.0.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/fulfill", [
        'items' => [['sales_order_item_id' => $lineItemId, 'quantity' => 5]],
    ], withIdempotencyKey($headers))->assertCreated();

    $this->getJson("/api/v1/sales-orders/{$salesOrderId}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.items.0.quantity_fulfilled', 5);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 7)
        ->assertJsonPath('data.0.quantity_reserved', 0);
});

test('sales order multi-line fulfill rolls back the entire fulfillment when one line over-fulfills', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-multi-over@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 50);

    $unit = $this->postJson('/api/v1/units', ['name' => 'Box', 'symbol' => 'box'], $headers)->assertCreated();
    $productB = $this->postJson('/api/v1/products', [
        'category_id' => $this->getJson('/api/v1/categories', $headers)->json('data.0.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Second Sold Item',
        'sku' => 'SO2-'.fake()->unique()->numerify('####'),
        'cost_price' => 3,
        'selling_price' => 6,
    ], withIdempotencyKey($headers))->assertCreated();
    $productC = $this->postJson('/api/v1/products', [
        'category_id' => $this->getJson('/api/v1/categories', $headers)->json('data.0.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Third Sold Item',
        'sku' => 'SO3-'.fake()->unique()->numerify('####'),
        'cost_price' => 4,
        'selling_price' => 8,
    ], withIdempotencyKey($headers))->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $productB->json('data.id'),
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 50,
    ], withIdempotencyKey($headers))->assertCreated();

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $productC->json('data.id'),
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 50,
    ], withIdempotencyKey($headers))->assertCreated();

    $salesOrder = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 10, 'unit_price' => 15],
            ['product_id' => $productB->json('data.id'), 'quantity' => 10, 'unit_price' => 6],
            ['product_id' => $productC->json('data.id'), 'quantity' => 10, 'unit_price' => 8],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $salesOrderId = $salesOrder->json('data.id');
    $lineOneId = $salesOrder->json('data.items.0.id');
    $lineTwoId = $salesOrder->json('data.items.1.id');
    $lineThreeId = $salesOrder->json('data.items.2.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)->assertOk();

    $response = $this->postJson("/api/v1/sales-orders/{$salesOrderId}/fulfill", [
        'items' => [
            ['sales_order_item_id' => $lineOneId, 'quantity' => 5],
            ['sales_order_item_id' => $lineTwoId, 'quantity' => 5],
            ['sales_order_item_id' => $lineThreeId, 'quantity' => 11],
        ],
    ], $headers);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['items.2.quantity']);

    expect(SalesFulfillment::query()->count())->toBe(0)
        ->and(SalesFulfillmentItem::query()->count())->toBe(0)
        ->and(StockMovement::query()->where('type', StockMovementType::SaleOut)->count())->toBe(0)
        ->and(SalesOrderItem::query()->sum('quantity_fulfilled'))->toBe(0);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_reserved', 10)
        ->assertJsonPath('data.0.quantity_on_hand', 50);
});

test('sales order cancel after partial fulfill releases only unfulfilled reservation', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-partial-cancel@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 20);

    $salesOrder = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 8, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $salesOrderId = $salesOrder->json('data.id');
    $lineItemId = $salesOrder->json('data.items.0.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/fulfill", [
        'items' => [['sales_order_item_id' => $lineItemId, 'quantity' => 3]],
    ], withIdempotencyKey($headers))->assertCreated();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/cancel", [], $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 17)
        ->assertJsonPath('data.0.quantity_reserved', 0)
        ->assertJsonPath('data.0.quantity_available', 17);
});

test('warehouse staff can fulfill sales orders but cannot confirm them', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-wh-staff@acme.test']);
    $ownerHeaders = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $ownerHeaders);
    seedStockForSales($this, $ownerHeaders, $catalog, 15);

    $salesOrder = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 4, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($ownerHeaders))->assertCreated();

    $salesOrderId = $salesOrder->json('data.id');
    $lineItemId = $salesOrder->json('data.items.0.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $ownerHeaders)->assertOk();

    $warehouseStaff = User::factory()->create(['email' => 'so-warehouse-staff@acme.test']);
    $warehouseStaff->organizations()->attach($org['organization_id'], ['role' => 'Warehouse Staff']);

    setPermissionsTeamId($org['organization_id']);
    $warehouseStaff->assignRole('Warehouse Staff');

    $staffLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'so-warehouse-staff@acme.test',
        'password' => 'password',
    ])->assertOk();

    $staffHeaders = $this->organizationContextHeaders(
        $staffLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->getJson("/api/v1/sales-orders/{$salesOrderId}", $staffHeaders)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/fulfill", [
        'items' => [['sales_order_item_id' => $lineItemId, 'quantity' => 4]],
    ], $staffHeaders)->assertCreated();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $staffHeaders)->assertForbidden();
});

test('sales order fulfill rejects draft sales orders', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'so-draft-fulfill@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);

    $salesOrder = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 2, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $salesOrderId = $salesOrder->json('data.id');
    $lineItemId = $salesOrder->json('data.items.0.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/fulfill", [
        'items' => [['sales_order_item_id' => $lineItemId, 'quantity' => 1]],
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});
