<?php

use App\Enums\PaymentStatus;
use App\Enums\StockMovementType;
use App\Models\Payment;
use App\Models\SalesOrder;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('sales order partial payment on shipped order keeps status shipped', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'pay-partial@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 30,
        'method' => 'cash',
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.status', 'completed')
        ->assertJsonPath('data.amount', '30.00');

    $this->getJson("/api/v1/sales-orders/{$order['sales_order_id']}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.amount_paid', '30.00')
        ->assertJsonPath('data.amount_due', '45.00');
});

test('sales order full payment on shipped order keeps status shipped', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'pay-delivered@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => $order['total_amount'],
        'method' => 'card',
    ], $headers)->assertCreated();

    $this->getJson("/api/v1/sales-orders/{$order['sales_order_id']}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'shipped')
        ->assertJsonPath('data.amount_paid', $order['total_amount'])
        ->assertJsonPath('data.amount_due', '0.00');
});

test('sales order deliver marks shipped order as delivered without requiring payment', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'deliver-shipped@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/deliver", [], $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'delivered')
        ->assertJsonPath('data.amount_paid', '0.00')
        ->assertJsonPath('data.amount_due', $order['total_amount']);
});

test('sales order payment on delivered order is allowed for invoice terms', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'pay-after-deliver@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/deliver", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => $order['total_amount'],
        'method' => 'bank_transfer',
    ], $headers)->assertCreated();

    $this->getJson("/api/v1/sales-orders/{$order['sales_order_id']}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'delivered')
        ->assertJsonPath('data.amount_paid', $order['total_amount'])
        ->assertJsonPath('data.amount_due', '0.00');
});

test('sales order cancel is blocked when payments have been recorded', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cancel-with-pay@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 20);

    $salesOrderId = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 4, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated()->json('data.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/pay", [
        'amount' => 30,
        'method' => 'cash',
    ], $headers)->assertCreated();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/cancel", [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    $this->getJson("/api/v1/sales-orders/{$salesOrderId}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.amount_paid', '30.00');

    expect(Payment::query()->count())->toBe(1);
});

test('sales order payment on confirmed order is allowed before fulfillment', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'pay-confirmed@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    seedStockForSales($this, $headers, $catalog, 20);

    $salesOrderId = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 4, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated()->json('data.id');

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/confirm", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$salesOrderId}/pay", [
        'amount' => 30,
        'method' => 'cash',
    ], $headers)->assertCreated();

    $this->getJson("/api/v1/sales-orders/{$salesOrderId}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.amount_paid', '30.00');
});

test('sales order overpayment returns 422 with no payment persisted', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'pay-over@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 100,
        'method' => 'cash',
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(Payment::query()->count())->toBe(0);
});

test('sales order payment rejects draft and cancelled orders', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'pay-invalid-status@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);

    $draftOrderId = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 2, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated()->json('data.id');

    $this->postJson("/api/v1/sales-orders/{$draftOrderId}/pay", [
        'amount' => 10,
        'method' => 'cash',
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);

    seedStockForSales($this, $headers, $catalog, 10);

    $cancelledOrderId = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 2, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated()->json('data.id');

    $this->postJson("/api/v1/sales-orders/{$cancelledOrderId}/confirm", [], $headers)->assertOk();
    $this->postJson("/api/v1/sales-orders/{$cancelledOrderId}/cancel", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$cancelledOrderId}/pay", [
        'amount' => 10,
        'method' => 'cash',
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['status']);
});

test('sales order goodwill refund does not write stock movements', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'refund-goodwill@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/deliver", [], $headers)->assertOk();
    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 20,
        'method' => 'cash',
    ], $headers)->assertCreated();

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/refund", [
        'amount' => 10,
        'method' => 'cash',
        'note' => 'Goodwill credit — customer keeps goods',
    ], $headers)->assertCreated();

    expect(\App\Models\StockMovement::query()->where('type', \App\Enums\StockMovementType::ReturnIn)->count())->toBe(0);

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 45);
});

test('sales order refund with return_items writes return_in and restores stock', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'refund-return@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog, 5);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => $order['total_amount'],
        'method' => 'cash',
    ], $headers)->assertCreated();

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/refund", [
        'amount' => $order['total_amount'],
        'method' => 'cash',
        'return_items' => [
            ['sales_order_item_id' => $order['line_item_id'], 'quantity' => 3],
        ],
    ], $headers)->assertCreated();

    $this->getJson('/api/v1/stocks', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.quantity_on_hand', 48);

    expect(\App\Models\StockMovement::query()->where('type', \App\Enums\StockMovementType::ReturnIn)->sum('quantity'))->toBe(3);

    $this->getJson("/api/v1/sales-orders/{$order['sales_order_id']}", $headers)
        ->assertOk()
        ->assertJsonPath('data.items.0.quantity_returned', 3)
        ->assertJsonPath('data.status', 'refunded');
});

test('sales order refund rejects over-return quantities', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'refund-over-return@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog, 5);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 30,
        'method' => 'cash',
    ], $headers)->assertCreated();

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/refund", [
        'amount' => 15,
        'method' => 'cash',
        'return_items' => [
            ['sales_order_item_id' => $order['line_item_id'], 'quantity' => 6],
        ],
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['return_items.0.quantity']);

    expect(\App\Models\StockMovement::query()->where('type', \App\Enums\StockMovementType::ReturnIn)->count())->toBe(0);
});

test('sales order full refund on delivered order transitions to refunded', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'refund-full@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/deliver", [], $headers)->assertOk();

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => $order['total_amount'],
        'method' => 'cash',
    ], $headers)->assertCreated();

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/refund", [
        'amount' => $order['total_amount'],
        'method' => 'cash',
        'note' => 'Customer return',
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.status', 'refunded');

    $this->getJson("/api/v1/sales-orders/{$order['sales_order_id']}", $headers)
        ->assertOk()
        ->assertJsonPath('data.status', 'refunded')
        ->assertJsonPath('data.amount_paid', '0.00');
});

test('sales order over-refund returns 422', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'refund-over@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 30,
        'method' => 'cash',
    ], $headers)->assertCreated();

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/refund", [
        'amount' => 50,
        'method' => 'cash',
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(Payment::query()->where('status', PaymentStatus::Refunded)->count())->toBe(0);
});

test('warehouse staff cannot pay sales orders but sales staff can', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'pay-role-split@acme.test']);
    $ownerHeaders = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $ownerHeaders);
    $order = createShippedSalesOrderForPayment($this, $ownerHeaders, $catalog);

    $warehouseStaff = User::factory()->create(['email' => 'pay-wh-staff@acme.test']);
    $warehouseStaff->organizations()->attach($org['organization_id'], ['role' => 'Warehouse Staff']);
    setPermissionsTeamId($org['organization_id']);
    $warehouseStaff->assignRole('Warehouse Staff');

    $staffLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'pay-wh-staff@acme.test',
        'password' => 'password',
    ])->assertOk();

    $warehouseHeaders = $this->organizationContextHeaders(
        $staffLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 10,
        'method' => 'cash',
    ], $warehouseHeaders)->assertForbidden();

    $salesStaff = User::factory()->create(['email' => 'pay-sales-staff@acme.test']);
    $salesStaff->organizations()->attach($org['organization_id'], ['role' => 'Sales Staff']);
    $salesStaff->assignRole('Sales Staff');

    $salesLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'pay-sales-staff@acme.test',
        'password' => 'password',
    ])->assertOk();

    $salesHeaders = $this->organizationContextHeaders(
        $salesLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 10,
        'method' => 'cash',
    ], $salesHeaders)->assertCreated();
});

test('payments are isolated between organizations', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'pay-tenant-a@acme.test']);
    $headersA = $this->organizationHeaders($orgA['token'], $orgA['organization_id']);
    $catalogA = createSalesCatalog($this, $headersA);
    $order = createShippedSalesOrderForPayment($this, $headersA, $catalogA);

    $paymentId = $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 20,
        'method' => 'cash',
    ], $headersA)->assertCreated()->json('data.id');

    $orgB = $this->registerOrganizationWithOwner(['email' => 'pay-tenant-b@acme.test']);
    $headersB = $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']);

    $this->getJson("/api/v1/payments/{$paymentId}", $headersB)->assertNotFound();
});

test('payments index can be filtered by sales order', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'pay-index@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $order = createShippedSalesOrderForPayment($this, $headers, $catalog);

    $this->postJson("/api/v1/sales-orders/{$order['sales_order_id']}/pay", [
        'amount' => 20,
        'method' => 'bank_transfer',
        'reference' => 'TXN-001',
    ], $headers)->assertCreated();

    $salesOrderClass = SalesOrder::class;

    $this->getJson("/api/v1/payments?filter[payable_type]={$salesOrderClass}&filter[payable_id]={$order['sales_order_id']}", $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.amount', '20.00')
        ->assertJsonPath('data.0.method', 'bank_transfer');
});

test('purchase order payment records against received purchase orders', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-pay@acme.test']);
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
        'amount' => $totalAmount,
        'method' => 'bank_transfer',
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.amount', $totalAmount);

    $this->getJson("/api/v1/purchase-orders/{$purchaseOrderId}", $headers)
        ->assertOk()
        ->assertJsonPath('data.amount_paid', $totalAmount)
        ->assertJsonPath('data.amount_due', '0.00');
});

test('purchase order overpayment returns 422', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-pay-over@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);

    $purchaseOrder = createDraftPurchaseOrder($this, $headers, $catalog)->assertCreated();
    $purchaseOrderId = $purchaseOrder->json('data.id');
    $lineItemId = $purchaseOrder->json('data.items.0.id');

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/send", [], $headers)->assertOk();
    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/receive", [
        'items' => [['purchase_order_item_id' => $lineItemId, 'quantity' => 10]],
    ], $headers)->assertCreated();

    $this->postJson("/api/v1/purchase-orders/{$purchaseOrderId}/pay", [
        'amount' => 9999,
        'method' => 'cash',
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(Payment::query()->count())->toBe(0);
});
