<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('product index supports filter by updated_after', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'sync-products@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $category = $this->postJson('/api/v1/categories', ['name' => 'Sync Cat'], $headers)->assertCreated();
    $unit = $this->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pc'], $headers)->assertCreated();

    $this->postJson('/api/v1/products', [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Original Product',
        'sku' => 'SYNC-001',
        'cost_price' => 1,
        'selling_price' => 2,
    ], $headers)->assertCreated();

    $cutoff = now()->addSecond()->toISOString();

    $this->travel(2)->seconds();

    $this->postJson('/api/v1/products', [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
        'name' => 'Newer Product',
        'sku' => 'SYNC-002',
        'cost_price' => 1,
        'selling_price' => 2,
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/products?filter[updated_after]='.urlencode($cutoff), $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sku', 'SYNC-002');
});

test('category index supports filter by updated_after', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'sync-categories@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/categories', ['name' => 'Old Category'], $headers)->assertCreated();

    $cutoff = now()->addSecond()->toISOString();

    $this->travel(2)->seconds();

    $this->postJson('/api/v1/categories', ['name' => 'Fresh Category'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/categories?filter[updated_after]='.urlencode($cutoff), $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Fresh Category');
});

test('unit index supports filter by updated_after', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'sync-units@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/units', ['name' => 'Old Unit', 'symbol' => 'ou'], $headers)->assertCreated();

    $cutoff = now()->addSecond()->toISOString();

    $this->travel(2)->seconds();

    $this->postJson('/api/v1/units', ['name' => 'Fresh Unit', 'symbol' => 'fu'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/units?filter[updated_after]='.urlencode($cutoff), $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.symbol', 'fu');
});
