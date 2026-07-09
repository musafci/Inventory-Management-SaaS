<?php

use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setUpPassport();
});

test('sales order create requires an idempotency key', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'idem-required@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);

    $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 1, 'unit_price' => 10],
        ],
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['idempotency_key']);
});

test('sequential duplicate idempotency key replays the cached sales order response', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'idem-seq@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $idempotencyKey = 'so-create-'.fake()->uuid();

    $payload = [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 2, 'unit_price' => 12],
        ],
    ];

    $first = $this->postJson('/api/v1/sales-orders', $payload, withIdempotencyKey($headers, $idempotencyKey))
        ->assertCreated()
        ->assertJsonPath('data.order_number', 'SO-000001');

    $second = $this->postJson('/api/v1/sales-orders', $payload, withIdempotencyKey($headers, $idempotencyKey))
        ->assertCreated()
        ->assertHeader('Idempotency-Replayed', 'true');

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(SalesOrder::query()->count())->toBe(1);
});

test('reusing an idempotency key with a different body is rejected', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'idem-mismatch@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);
    $idempotencyKey = 'so-create-'.fake()->uuid();

    $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 2, 'unit_price' => 12],
        ],
    ], withIdempotencyKey($headers, $idempotencyKey))->assertCreated();

    $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 3, 'unit_price' => 12],
        ],
    ], withIdempotencyKey($headers, $idempotencyKey))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['idempotency_key']);

    expect(SalesOrder::query()->count())->toBe(1);
});

test('purchase order create replays cached response for the same idempotency key', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'idem-po@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createPurchasingCatalog($this, $headers);
    $idempotencyKey = 'po-create-'.fake()->uuid();

    $payload = [
        'supplier_id' => $catalog['supplier_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity_ordered' => 4, 'unit_cost' => 5],
        ],
    ];

    $first = $this->postJson('/api/v1/purchase-orders', $payload, withIdempotencyKey($headers, $idempotencyKey))
        ->assertCreated()
        ->assertJsonPath('data.po_number', 'PO-000001');

    $second = $this->postJson('/api/v1/purchase-orders', $payload, withIdempotencyKey($headers, $idempotencyKey))
        ->assertCreated()
        ->assertHeader('Idempotency-Replayed', 'true');

    expect($second->json('data.id'))->toBe($first->json('data.id'))
        ->and(\App\Models\PurchaseOrder::query()->count())->toBe(1);
});
