<?php

use App\Enums\StockMovementType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('stock valuation report sums on-hand quantity at cost price', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'report-val@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $catalog['warehouse_id'],
        'product_id' => $catalog['product_id'],
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 10,
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/reports/stock-valuation', $headers);

    $response->assertOk()
        ->assertJsonPath('data.total_units', 10)
        ->assertJsonPath('data.total_value', '50.00')
        ->assertJsonPath('data.valuation_basis', 'quantity_on_hand')
        ->assertJsonCount(1, 'data.by_warehouse');
});

test('low stock report lists products at or below reorder point', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'report-low@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $category = $this->postJson('/api/v1/categories', ['name' => 'Report Cat'], $headers)->assertCreated();
    $unit = $this->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)->assertCreated();
    $product = $this->postJson('/api/v1/products', [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Low Item',
        'sku' => 'LOW-'.fake()->unique()->numerify('####'),
        'cost_price' => 5,
        'selling_price' => 15,
        'reorder_point' => 10,
    ], $headers)->assertCreated();

    $this->postJson('/api/v1/warehouses', ['name' => 'Report Warehouse'], $headers)->assertCreated();
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $this->postJson('/api/v1/stock-movements', [
        'warehouse_id' => $warehouseId,
        'product_id' => $product->json('data.id'),
        'type' => StockMovementType::AdjustmentIn->value,
        'quantity' => 8,
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/reports/low-stock', $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.quantity_available', 8)
        ->assertJsonPath('data.0.reorder_point', 10);
});

test('sales summary report aggregates orders and payments', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'report-sales@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 30,
        'method' => 'cash',
        'paid_at' => '2026-07-09',
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/reports/sales-summary?from=2026-07-09&to=2026-07-09&payment_from=2026-07-09&payment_to=2026-07-09', $headers);

    $response->assertOk()
        ->assertJsonPath('data.filters.order_date.from', '2026-07-09')
        ->assertJsonPath('data.filters.payment_date.from', '2026-07-09')
        ->assertJsonPath('data.order_count', 1)
        ->assertJsonPath('data.total_amount', $order['total_amount'])
        ->assertJsonPath('data.payments_received', '30.00')
        ->assertJsonPath('data.by_status.0.status', 'shipped');
});

test('sales summary separates order_date and payment_date filters', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'report-split-dates@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 30,
        'method' => 'cash',
        'paid_at' => '2026-08-15',
    ], $headers)->assertCreated();

    $this->getJson('/api/v1/reports/sales-summary?from=2026-07-09&to=2026-07-09', $headers)
        ->assertOk()
        ->assertJsonPath('data.order_count', 1)
        ->assertJsonPath('data.payments_received', null);

    $this->getJson('/api/v1/reports/sales-summary?payment_from=2026-08-01&payment_to=2026-08-31', $headers)
        ->assertOk()
        ->assertJsonPath('data.order_count', 1)
        ->assertJsonPath('data.payments_received', '30.00');
});

test('purchase summary report aggregates orders and payments', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'report-purchase@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrder = createDraftPurchaseOrder($this, $headers, $catalog)->assertCreated();
    $purchaseOrderId = $purchaseOrder->json('data.id');
    $lineItemId = $purchaseOrder->json('data.items.0.id');
    $totalAmount = $purchaseOrder->json('data.total_amount');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headers)->assertOk();
    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
        'items' => [['purchase_order_item_id' => $lineItemId, 'quantity' => 10]],
    ], $headers)->assertCreated();

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/pay", [
        'amount' => 50,
        'method' => 'bank_transfer',
        'paid_at' => '2026-07-09',
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/reports/purchase-summary?from=2026-07-09&to=2026-07-09&payment_from=2026-07-09&payment_to=2026-07-09', $headers);

    $response->assertOk()
        ->assertJsonPath('data.order_count', 1)
        ->assertJsonPath('data.total_amount', $totalAmount)
        ->assertJsonPath('data.payments_made', '50.00')
        ->assertJsonPath('data.by_status.0.status', 'partially_received');
});

test('org owner can view reports immediately after registration', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'report-fresh@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->getJson('/api/v1/reports/low-stock', $headers)->assertOk();
});

test('reports are isolated between organizations', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'report-tenant-a@acme.test']);

    $organizationA = \App\Models\Organization::query()->findOrFail($orgA['organization_id']);
    app()->instance('currentOrganization', $organizationA);
    setPermissionsTeamId($organizationA->id);

    \App\Models\SalesOrder::withoutOrganizationScope()->create([
        'organization_id' => $organizationA->id,
        'customer_id' => \App\Models\Customer::withoutOrganizationScope()->create([
            'organization_id' => $organizationA->id,
            'name' => 'Tenant A Customer',
        ])->id,
        'warehouse_id' => \App\Models\Warehouse::withoutOrganizationScope()->create([
            'organization_id' => $organizationA->id,
            'name' => 'Tenant A Warehouse',
            'is_default' => true,
        ])->id,
        'order_number' => 'SO-000099',
        'status' => \App\Enums\SalesOrderStatus::Shipped,
        'order_date' => '2026-07-09',
        'total_amount' => 75.00,
    ]);

    app()->forgetInstance('currentOrganization');

    $orgB = $this->registerOrganizationWithOwner(['email' => 'report-tenant-b@acme.test']);
    $headersB = $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']);

    $this->getJson('/api/v1/reports/sales-summary', $headersB)
        ->assertOk()
        ->assertJsonPath('data.order_count', 0);
});

test('warehouse staff without reports permission cannot view reports', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'report-wh-staff@acme.test']);
    $ownerHeaders = $this->organizationHeaders($org['token'], $org['organization_id']);
    createSalesCatalog($this, $ownerHeaders);

    $warehouseStaff = User::factory()->create(['email' => 'report-wh-staff-user@acme.test']);
    $warehouseStaff->organizations()->attach($org['organization_id'], ['role' => 'Warehouse Staff']);

    setPermissionsTeamId($org['organization_id']);
    $warehouseStaff->assignRole('Warehouse Staff');

    $staffLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'report-wh-staff-user@acme.test',
        'password' => 'password',
    ])->assertOk();

    $staffHeaders = $this->organizationContextHeaders(
        $staffLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->getJson('/api/v1/reports/low-stock', $staffHeaders)->assertForbidden();
});
