<?php

use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('customer store creates a customer', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cust-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $response = $this->postJson('/api/v1/customers', [
        'name' => 'Retail Buyer',
        'email' => 'buyer@retail.test',
        'phone' => '+15550002222',
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Retail Buyer');
});

test('customers are isolated between organizations', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'cust-tenant-a@acme.test']);
    $headersA = $this->organizationHeaders($orgA['token'], $orgA['organization_id']);
    $customerId = $this->postJson('/api/v1/customers', ['name' => 'Org A Customer'], $headersA)
        ->assertCreated()
        ->json('data.id');

    $orgB = $this->registerOrganizationWithOwner(['email' => 'cust-tenant-b@acme.test']);
    $headersB = $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']);

    $this->getJson("/api/v1/customers/{$customerId}", $headersB)->assertNotFound();
});
