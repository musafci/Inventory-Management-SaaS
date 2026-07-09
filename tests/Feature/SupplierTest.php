<?php

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('supplier store creates a supplier', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'sup-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $response = $this->postJson('/api/v1/suppliers', [
        'name' => 'Global Parts Co',
        'contact_person' => 'Pat Supplier',
        'email' => 'pat@global.test',
        'phone' => '+15550001111',
        'address' => '9 Vendor Road',
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Global Parts Co')
        ->assertJsonPath('data.organization_id', $org['organization_id']);
});

test('supplier delete is blocked when purchase orders exist', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'sup-delete@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $supplierId = $this->postJson('/api/v1/suppliers', ['name' => 'Locked Supplier'], $headers)
        ->assertCreated()
        ->json('data.id');

    $this->postJson('/api/v1/categories', ['name' => 'Cat'], $headers)->assertCreated();
    $unit = $this->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'ea'], $headers)->assertCreated();
    $product = $this->postJson('/api/v1/products', [
        'category_id' => $this->getJson('/api/v1/categories', $headers)->json('data.0.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Widget',
        'sku' => 'W-001',
        'cost_price' => 1,
        'selling_price' => 2,
    ], $headers)->assertCreated();
    $this->postJson('/api/v1/warehouses', ['name' => 'WH'], $headers)->assertCreated();

    $this->postJson('/api/v1/purchase-orders', [
        'supplier_id' => $supplierId,
        'warehouse_id' => $this->getJson('/api/v1/warehouses', $headers)->json('data.0.id'),
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $product->json('data.id'), 'quantity_ordered' => 1, 'unit_cost' => 1],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $this->deleteJson("/api/v1/suppliers/{$supplierId}", [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['supplier']);

    expect(Supplier::query()->whereKey($supplierId)->exists())->toBeTrue();
});

test('suppliers are isolated between organizations', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'sup-tenant-a@acme.test']);
    $headersA = $this->organizationHeaders($orgA['token'], $orgA['organization_id']);
    $supplierId = $this->postJson('/api/v1/suppliers', ['name' => 'Org A Supplier'], $headersA)
        ->assertCreated()
        ->json('data.id');

    $orgB = $this->registerOrganizationWithOwner(['email' => 'sup-tenant-b@acme.test']);
    $headersB = $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']);

    $this->getJson("/api/v1/suppliers/{$supplierId}", $headersB)->assertNotFound();
});
