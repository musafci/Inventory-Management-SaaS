<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Token\Parser;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('authenticated user can logout and revoke token', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'logout@acme.test',
    ]))->assertCreated();

    $token = $register->json('data.token.access_token');
    $tokenId = (new Parser(new JoseEncoder()))->parse($token)->claims()->get('jti');

    $this->postJson('/api/v1/auth/logout', [], [
        'Authorization' => 'Bearer '.$token,
    ])->assertNoContent();

    $this->assertDatabaseHas('oauth_access_tokens', [
        'id' => $tokenId,
        'revoked' => true,
    ]);
});

test('dashboard report endpoint returns cached aggregates', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'dashboard@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    Cache::flush();

    $this->getJson('/api/v1/reports/dashboard', $headers)
        ->assertOk()
        ->assertJsonStructure([
            'data' => [
                'total_products',
                'total_stock_items',
                'stock_value',
                'low_stock_count',
                'pending_purchase_orders',
                'pending_sales_orders',
            ],
        ]);

    $this->assertTrue(Cache::has('org:'.$org['organization_id'].':reports:dashboard'));
});

test('user can queue a report export', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'export@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/reports/exports', [
        'type' => 'low_stock',
    ], $headers)->assertStatus(202)
        ->assertJsonPath('data.type', 'low_stock')
        ->assertJsonPath('data.status', 'pending');
});

test('purchase order send dispatches order status notification job', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'po-notify@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    \Illuminate\Support\Facades\Queue::fake();

    $supplierId = $this->postJson('/api/v1/suppliers', [
        'name' => 'Notify Supplier',
    ], $headers)->json('data.id');

    $warehouseId = $this->postJson('/api/v1/warehouses', [
        'name' => 'Notify Warehouse',
        'is_default' => true,
    ], $headers)->json('data.id');

    $categoryId = $this->postJson('/api/v1/categories', ['name' => 'Notify Cat'], $headers)->json('data.id');
    $unitId = $this->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pc'], $headers)->json('data.id');

    $productId = $this->postJson('/api/v1/products', [
        'category_id' => $categoryId,
        'unit_id' => $unitId,
        'name' => 'Notify Product',
        'sku' => 'NOTIFY-001',
        'cost_price' => 10,
        'selling_price' => 20,
        'is_active' => true,
    ], $headers)->json('data.id');

    $poId = $this->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplierId,
        'warehouse_id' => $warehouseId,
        'order_date' => now()->toDateString(),
        'items' => [[
            'product_id' => $productId,
            'quantity_ordered' => 5,
            'unit_cost' => 10,
        ]],
    ], withIdempotencyKey($headers))->json('data.id');

    $this->postJson("/api/v1/purchase-orders/{$poId}/send", [], $headers)->assertOk();

    \Illuminate\Support\Facades\Queue::assertPushed(\App\Jobs\SendOrderStatusNotificationJob::class);
});
