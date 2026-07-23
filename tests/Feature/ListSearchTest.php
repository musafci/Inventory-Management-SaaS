<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('customer index supports search query parameter', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'search-customers@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/customers', ['name' => 'Alpha Retail', 'email' => 'alpha@test.com'], $headers)->assertCreated();
    $this->postJson('/api/v1/customers', ['name' => 'Beta Wholesale', 'email' => 'beta@test.com'], $headers)->assertCreated();

    $this->getJson('/api/v1/customers?search=Alpha', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Retail');
});

test('supplier index supports search query parameter', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'search-suppliers@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/suppliers', ['name' => 'Acme Supplies'], $headers)->assertCreated();
    $this->postJson('/api/v1/suppliers', ['name' => 'Global Parts'], $headers)->assertCreated();

    $this->getJson('/api/v1/suppliers?search=Global', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Global Parts');
});

test('category index supports search query parameter', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'search-categories@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/categories', ['name' => 'Electronics'], $headers)->assertCreated();
    $this->postJson('/api/v1/categories', ['name' => 'Groceries'], $headers)->assertCreated();

    $this->getJson('/api/v1/categories?search=Groc', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Groceries');
});
