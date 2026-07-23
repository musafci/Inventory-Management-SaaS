<?php

use App\Models\User;
use App\Services\Web\WebSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

function webSessionForOrganization(array $org): void
{
    $register = $org['response'];
    $user = User::query()->where('email', $register->json('data.user.email'))->firstOrFail();
    $organizations = collect($register->json('data.organizations'))
        ->map(fn (array $organization): array => [
            'id' => $organization['id'],
            'name' => $organization['name'],
            'slug' => $organization['slug'],
            'role' => $organization['role'] ?? 'owner',
        ])
        ->values()
        ->all();

    session([
        'auth_token' => $org['token'],
        'refresh_token' => $register->json('data.token.refresh_token'),
        'token_expires_at' => now()->addHour()->toIso8601String(),
        'user_id' => $user->id,
        'user_name' => $user->name,
        'user_email' => $user->email,
        'organizations' => $organizations,
        'organization_id' => $org['organization_id'],
    ]);

    app(WebSessionService::class)->syncPermissionsForActiveOrganization();
}

test('sales order print page renders created order', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'print-so@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    webSessionForOrganization($org);

    $catalog = createSalesCatalog($this, $headers);

    $order = $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 2, 'unit_price' => 15],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $orderNumber = $order->json('data.order_number');
    $orderId = $order->json('data.id');

    $this->get("/sales-orders/{$orderId}/print")
        ->assertOk()
        ->assertSee('Sales Order')
        ->assertSee($orderNumber)
        ->assertSee('Walk-in Customer')
        ->assertSee('Sold Item');
});

test('purchase order print page renders created order', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'print-po@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    webSessionForOrganization($org);

    $supplier = $this->postJson('/api/v1/suppliers', ['name' => 'Print Supplier Co.'], $headers)->assertCreated();
    $category = $this->postJson('/api/v1/categories', ['name' => 'PO Cat'], $headers)->assertCreated();
    $unit = $this->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)->assertCreated();
    $product = $this->postJson('/api/v1/products', [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Purchased Widget',
        'sku' => 'PO-PRINT-001',
        'cost_price' => 8,
        'selling_price' => 12,
    ], $headers)->assertCreated();
    $this->postJson('/api/v1/warehouses', ['name' => 'Receiving Warehouse'], $headers)->assertCreated();
    $warehouseId = $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id');

    $order = $this->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplier->json('data.id'),
        'warehouse_id' => $warehouseId,
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $product->json('data.id'), 'quantity_ordered' => 5, 'unit_cost' => 8],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $poNumber = $order->json('data.po_number');
    $orderId = $order->json('data.id');

    $this->get("/purchase-orders/{$orderId}/print")
        ->assertOk()
        ->assertSee('Purchase Order')
        ->assertSee($poNumber)
        ->assertSee('Print Supplier Co.')
        ->assertSee('Purchased Widget');
});

test('order print routes require authentication', function () {
    $this->get('/sales-orders/1/print')->assertRedirect('/login');
    $this->get('/purchase-orders/1/print')->assertRedirect('/login');
});
