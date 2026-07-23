<?php

use App\Models\Customer;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('products can be imported from csv', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'import-products@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $categoryName = $this->getJson('/api/v1/categories/'.$catalog['category_id'], $headers)
        ->json('data.name');
    $unitName = $this->getJson('/api/v1/units/'.$catalog['unit_id'], $headers)
        ->json('data.name');

    $csv = implode("\n", [
        'name,sku,category,unit,cost_price,selling_price,barcode,tax_rate,reorder_point,is_active',
        "Imported Mouse,IMP-001,{$categoryName},{$unitName},10.00,19.99,1234567890123,5,10,yes",
        "Imported Keyboard,IMP-002,{$categoryName},{$unitName},20.00,39.99,,0,,1",
    ]);

    $response = $this->postJson('/api/v1/products/import', ['csv' => $csv], $headers);

    $response->assertOk()
        ->assertJsonPath('data.imported', 2)
        ->assertJsonPath('data.failed', 0);

    $this->assertDatabaseHas('products', [
        'organization_id' => $org['organization_id'],
        'sku' => 'IMP-001',
        'name' => 'Imported Mouse',
    ]);
    $this->assertDatabaseHas('products', [
        'organization_id' => $org['organization_id'],
        'sku' => 'IMP-002',
        'name' => 'Imported Keyboard',
    ]);
});

test('product import reports row errors for invalid rows', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'import-products-errors@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    createProductCatalog($this, $headers);

    $csv = implode("\n", [
        'name,sku,category,unit,cost_price,selling_price',
        'Bad Product,BAD-001,Missing Category,Missing Unit,10.00,19.99',
    ]);

    $response = $this->postJson('/api/v1/products/import', ['csv' => $csv], $headers);

    $response->assertOk()
        ->assertJsonPath('data.imported', 0)
        ->assertJsonPath('data.failed', 1)
        ->assertJsonPath('data.errors.0.row', 2);

    expect(Product::query()->where('sku', 'BAD-001')->exists())->toBeFalse();
});

test('product import rejects csv missing required columns', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'import-products-invalid@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $csv = "name,sku\nOnly Name,SKU-1";

    $this->postJson('/api/v1/products/import', ['csv' => $csv], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['csv']);
});

test('customers can be imported from csv', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'import-customers@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $csv = implode("\n", [
        'name,email,phone,address',
        'Retail Buyer,buyer@retail.test,+15550001111,123 Main St',
        'Wholesale Partner,,+15550002222,',
    ]);

    $response = $this->postJson('/api/v1/customers/import', ['csv' => $csv], $headers);

    $response->assertOk()
        ->assertJsonPath('data.imported', 2)
        ->assertJsonPath('data.failed', 0);

    $this->assertDatabaseHas('customers', [
        'organization_id' => $org['organization_id'],
        'name' => 'Retail Buyer',
        'email' => 'buyer@retail.test',
    ]);
    $this->assertDatabaseHas('customers', [
        'organization_id' => $org['organization_id'],
        'name' => 'Wholesale Partner',
        'email' => null,
    ]);
});

test('customer import reports duplicate email errors', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'import-customers-dup@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    Customer::factory()->create([
        'organization_id' => $org['organization_id'],
        'name' => 'Existing Customer',
        'email' => 'dup@retail.test',
    ]);

    $csv = implode("\n", [
        'name,email',
        'Duplicate Customer,dup@retail.test',
    ]);

    $response = $this->postJson('/api/v1/customers/import', ['csv' => $csv], $headers);

    $response->assertOk()
        ->assertJsonPath('data.imported', 0)
        ->assertJsonPath('data.failed', 1)
        ->assertJsonPath('data.errors.0.messages.0', 'Email already exists.');
});

test('customer import requires create permission', function () {
    $owner = $this->registerOrganizationWithOwner(['email' => 'import-customers-perm@acme.test']);
    $headers = $this->organizationHeaders($owner['token'], $owner['organization_id']);

    $member = \App\Models\User::factory()->create([
        'email' => 'viewer-import@acme.test',
        'password' => bcrypt('password123'),
    ]);
    $member->organizations()->attach($owner['organization_id'], ['role' => 'Viewer']);
    setPermissionsTeamId($owner['organization_id']);
    $member->assignRole('Viewer');

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'viewer-import@acme.test',
        'password' => 'password123',
    ])->assertOk();

    $viewerHeaders = $this->organizationHeaders(
        $login->json('data.token.access_token'),
        $owner['organization_id'],
    );

    $csv = "name\nRead Only Customer";

    $this->postJson('/api/v1/customers/import', ['csv' => $csv], $viewerHeaders)
        ->assertForbidden();
});

test('suppliers can be imported from csv', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'import-suppliers@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $csv = implode("\n", [
        'name,contact_person,email,phone,address',
        'Acme Supplies,Jane Doe,jane@acme.test,+15550001111,123 Supply St',
        'Global Parts,,parts@global.test,+15550002222,',
    ]);

    $response = $this->postJson('/api/v1/suppliers/import', ['csv' => $csv], $headers);

    $response->assertOk()
        ->assertJsonPath('data.imported', 2)
        ->assertJsonPath('data.failed', 0);

    $this->assertDatabaseHas('suppliers', [
        'organization_id' => $org['organization_id'],
        'name' => 'Acme Supplies',
        'contact_person' => 'Jane Doe',
    ]);
    $this->assertDatabaseHas('suppliers', [
        'organization_id' => $org['organization_id'],
        'name' => 'Global Parts',
    ]);
});

test('supplier import reports duplicate name errors', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'import-suppliers-dup@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/suppliers', ['name' => 'Existing Supplier'], $headers)->assertCreated();

    $csv = implode("\n", [
        'name',
        'Existing Supplier',
    ]);

    $response = $this->postJson('/api/v1/suppliers/import', ['csv' => $csv], $headers);

    $response->assertOk()
        ->assertJsonPath('data.imported', 0)
        ->assertJsonPath('data.failed', 1)
        ->assertJsonPath('data.errors.0.messages.0', 'Supplier name already exists.');
});
