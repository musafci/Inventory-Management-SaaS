<?php

use App\Enums\StockMovementType;
use App\Models\GoodsReceipt;
use App\Models\GoodsReceiptItem;
use App\Models\PurchaseOrderItem;
use App\Models\StockMovement;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('purchase order store creates a draft purchase order with line items', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $response = createDraftPurchaseOrder($this, $headers, $catalog);

    $response->assertCreated()
        ->assertJsonPath('data.status', 'draft')
        ->assertJsonPath('data.po_number', 'PO-000001')
        ->assertJsonPath('data.total_amount', '100.00')
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.quantity_ordered', 20)
        ->assertJsonPath('data.items.0.quantity_received', 0);
});

test('purchase order send transitions draft to sent', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-send@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrderId = createDraftPurchaseOrder($this, $headers, $catalog)->json('data.id');

    $response = $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headers);

    $response->assertOk()
        ->assertJsonPath('data.status', 'sent');
});

test('purchase order partial receive writes purchase_in stock and updates status', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-partial@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrder = createDraftPurchaseOrder($this, $headers, $catalog)->assertCreated();
    $purchaseOrderId = $purchaseOrder->json('data.id');
    $lineItemId = $purchaseOrder->json('data.items.0.id');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headers)->assertOk();

    $response = $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
        'items' => [
            ['purchase_order_item_id' => $lineItemId, 'quantity' => 8],
        ],
        'note' => 'First delivery',
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.note', 'First delivery');

    $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'partially_received')
        ->assertJsonPath('data.items.0.quantity_received', 8);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 8);

    $movement = StockMovement::query()->first();
    expect($movement->type)->toBe(StockMovementType::PurchaseIn)
        ->and($movement->quantity)->toBe(8)
        ->and($movement->reference_type)->toBe(GoodsReceipt::class);
});

test('purchase order full receive completes remaining quantity and marks received', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-full@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrder = createDraftPurchaseOrder($this, $headers, $catalog)->assertCreated();
    $purchaseOrderId = $purchaseOrder->json('data.id');
    $lineItemId = $purchaseOrder->json('data.items.0.id');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headers)->assertOk();

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
        'items' => [['purchase_order_item_id' => $lineItemId, 'quantity' => 20]],
    ], withIdempotencyKey($headers))->assertCreated();

    $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'received')
        ->assertJsonPath('data.items.0.quantity_received', 20);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 20);

    expect(StockMovement::query()->where('type', StockMovementType::PurchaseIn)->count())->toBe(1);
});

test('purchase order receive rejects quantities above remaining ordered amount', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-over@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrder = createDraftPurchaseOrder($this, $headers, $catalog)->assertCreated();
    $purchaseOrderId = $purchaseOrder->json('data.id');
    $lineItemId = $purchaseOrder->json('data.items.0.id');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headers)->assertOk();

    $response = $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
        'items' => [['purchase_order_item_id' => $lineItemId, 'quantity' => 25]],
    ], $headers);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.quantity']);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonCount(0, 'data');

    expect(StockMovement::query()->count())->toBe(0);
});

test('purchase order multi-line receive rolls back the entire receipt when one line over-receives', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-multi-over@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $unit = $this->postJson('/api/v1/units', ['name' => 'Box', 'symbol' => 'box'], $headers)->assertCreated();
    $productB = $this->postJson('/api/v1/products', [
        'category_id' => $this->getJson('/api/v1/categories', $headers)->json('data.0.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Second Item',
        'sku' => 'PO2-'.fake()->unique()->numerify('####'),
        'cost_price' => 3,
        'selling_price' => 6,
    ], withIdempotencyKey($headers))->assertCreated();
    $productC = $this->postJson('/api/v1/products', [
        'category_id' => $this->getJson('/api/v1/categories', $headers)->json('data.0.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Third Item',
        'sku' => 'PO3-'.fake()->unique()->numerify('####'),
        'cost_price' => 4,
        'selling_price' => 8,
    ], withIdempotencyKey($headers))->assertCreated();

    $purchaseOrder = $this->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $catalog['supplier_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity_ordered' => 10, 'unit_cost' => 5],
            ['product_id' => $productB->json('data.id'), 'quantity_ordered' => 10, 'unit_cost' => 3],
            ['product_id' => $productC->json('data.id'), 'quantity_ordered' => 10, 'unit_cost' => 4],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $purchaseOrderId = $purchaseOrder->json('data.id');
    $lineOneId = $purchaseOrder->json('data.items.0.id');
    $lineTwoId = $purchaseOrder->json('data.items.1.id');
    $lineThreeId = $purchaseOrder->json('data.items.2.id');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headers)->assertOk();

    $response = $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
        'items' => [
            ['purchase_order_item_id' => $lineOneId, 'quantity' => 5],
            ['purchase_order_item_id' => $lineTwoId, 'quantity' => 5],
            ['purchase_order_item_id' => $lineThreeId, 'quantity' => 11],
        ],
    ], $headers);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['items.2.quantity']);

    expect(GoodsReceipt::query()->count())->toBe(0)
        ->and(GoodsReceiptItem::query()->count())->toBe(0)
        ->and(StockMovement::query()->count())->toBe(0)
        ->and(PurchaseOrderItem::query()->sum('quantity_received'))->toBe(0);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonCount(0, 'data');
});

test('warehouse staff can receive goods but cannot manage purchase orders commercially', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-wh-staff@acme.test']);
    $ownerHeaders = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $ownerHeaders);

    $purchaseOrder = createDraftPurchaseOrder($this, $ownerHeaders, $catalog)->assertCreated();
    $purchaseOrderId = $purchaseOrder->json('data.id');
    $lineItemId = $purchaseOrder->json('data.items.0.id');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $ownerHeaders)->assertOk();

    $warehouseStaff = User::factory()->create(['email' => 'warehouse-staff@acme.test']);
    $warehouseStaff->organizations()->attach($org['organization_id'], ['role' => 'Warehouse Staff']);

    setPermissionsTeamId($org['organization_id']);
    $warehouseStaff->assignRole('Warehouse Staff');

    $staffLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'warehouse-staff@acme.test',
        'password' => 'password',
    ])->assertOk();

    $staffHeaders = $this->organizationContextHeaders(
        $staffLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}", $staffHeaders)->assertOk();

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
        'items' => [['purchase_order_item_id' => $lineItemId, 'quantity' => 6]],
    ], $staffHeaders)->assertCreated();

    $this->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $catalog['supplier_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity_ordered' => 1, 'unit_cost' => 5],
        ],
    ], withIdempotencyKey($staffHeaders))->assertForbidden();

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/cancel", [], $staffHeaders)->assertForbidden();
});

test('purchase order receive rejects draft purchase orders', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-draft-recv@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrder = createDraftPurchaseOrder($this, $headers, $catalog)->assertCreated();
    $purchaseOrderId = $purchaseOrder->json('data.id');
    $lineItemId = $purchaseOrder->json('data.items.0.id');

    $response = $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
        'items' => [['purchase_order_item_id' => $lineItemId, 'quantity' => 5]],
    ], $headers);

    $response->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('purchase order update is only allowed while draft', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-update@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrderId = createDraftPurchaseOrder($this, $headers, $catalog)->json('data.id');

    $this->putJson("/api/v1/purchase-orders/{$purchaseOrderId}", [
        'expected_date' => '2026-07-20',
    ], $headers)
        ->assertOk()
        ->assertJsonPath('data.expected_date', '2026-07-20');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headers)->assertOk();

    $this->putJson("/api/v1/purchase-orders/{$purchaseOrderId}", [
        'expected_date' => '2026-07-25',
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('purchase order cancel is allowed for draft and sent statuses', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-cancel@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrderId = createDraftPurchaseOrder($this, $headers, $catalog)->json('data.id');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/cancel", [], $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');
});

test('purchase orders are isolated between organizations', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'po-tenant-a@acme.test']);
    $headersA = $this->organizationHeaders($orgA['token'], $orgA['organization_id']);
    $catalogA = createPurchasingCatalog($this, $headersA);
    $purchaseOrderId = createDraftPurchaseOrder($this, $headersA, $catalogA)->json('data.id');

    $orgB = $this->registerOrganizationWithOwner(['email' => 'po-tenant-b@acme.test']);
    $headersB = $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']);

    $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}", $headersB)->assertNotFound();
    $this->putJson("/api/v1/purchase-orders/{$purchaseOrderId}", ['expected_date' => '2026-08-01'], $headersB)->assertNotFound();
    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headersB)->assertNotFound();
});

test('purchase order line item discount reduces subtotal and total amount', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-discount@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    createDraftPurchaseOrder($this, $headers, $catalog, [
        'items' => [
            [
                'product_id' => $catalog['product_id'],
                'quantity_ordered' => 20,
                'unit_cost' => 5,
                'discount' => 10,
            ],
        ],
    ])
        ->assertCreated()
        ->assertJsonPath('data.total_amount', '90.00')
        ->assertJsonPath('data.total_discount', '10.00')
        ->assertJsonPath('data.gross_subtotal', '100.00')
        ->assertJsonPath('data.items.0.discount', '10.00')
        ->assertJsonPath('data.items.0.subtotal', '90.00');
});

test('purchase order rejects discount greater than line total', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-discount-invalid@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    createDraftPurchaseOrder($this, $headers, $catalog, [
        'items' => [
            [
                'product_id' => $catalog['product_id'],
                'quantity_ordered' => 10,
                'unit_cost' => 5,
                'discount' => 60,
            ],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['items.0.discount']);
});
