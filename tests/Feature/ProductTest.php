<?php

use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

/**
 * @return array{category_id: int, unit_id: int}
 */
function createProductCatalog(object $test, array $headers): array
{
    $category = $test->postJson('/api/v1/categories', ['name' => 'Electronics'], $headers)->assertCreated();
    $unit = $test->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)->assertCreated();

    return [
        'category_id' => $category->json('data.id'),
        'unit_id' => $unit->json('data.id'),
    ];
}

/**
 * @param  array{category_id: int, unit_id: int}  $catalog
 * @return array<string, mixed>
 */
function validProductPayload(array $catalog, array $overrides = []): array
{
    $suffix = fake()->unique()->numerify('######');

    return array_merge([
        'category_id' => $catalog['category_id'],
        'unit_id' => $catalog['unit_id'],
        'name' => 'Wireless Mouse',
        'sku' => 'WM-'.$suffix,
        'barcode' => fake()->unique()->numerify('############'),
        'cost_price' => 10.50,
        'selling_price' => 19.99,
        'tax_rate' => 5,
        'reorder_point' => 25,
        'is_active' => true,
    ], $overrides);
}

test('product index lists products for the current organization', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-index@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $this->postJson('/api/v1/products', validProductPayload($catalog), $headers)->assertCreated();

    $response = $this->getJson('/api/v1/products', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Wireless Mouse')
        ->assertJsonStructure([
            'data' => [[
                'id', 'name', 'sku', 'barcode', 'category_id', 'unit_id',
                'cost_price', 'selling_price', 'tax_rate', 'reorder_point', 'is_active', 'organization_id',
            ]],
            'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']],
        ]);
});

test('product index supports filter by category_id', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-cat-filter@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $otherCategory = $this->postJson('/api/v1/categories', ['name' => 'Other'], $headers)->assertCreated();

    $this->postJson('/api/v1/products', validProductPayload($catalog, ['sku' => 'A-001']), $headers)->assertCreated();
    $this->postJson('/api/v1/products', validProductPayload([
        'category_id' => $otherCategory->json('data.id'),
        'unit_id' => $catalog['unit_id'],
    ], ['name' => 'Other Product', 'sku' => 'B-001']), $headers)->assertCreated();

    $response = $this->getJson('/api/v1/products?filter[category_id]='.$catalog['category_id'], $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sku', 'A-001');
});

test('product index supports filter by sku', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-sku-filter@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $this->postJson('/api/v1/products', validProductPayload($catalog, ['sku' => 'ALPHA-001']), $headers)->assertCreated();
    $this->postJson('/api/v1/products', validProductPayload($catalog, ['name' => 'Beta', 'sku' => 'BETA-999']), $headers)->assertCreated();

    $response = $this->getJson('/api/v1/products?filter[sku]=ALPHA', $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sku', 'ALPHA-001');
});

test('product index supports search across name sku and barcode', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-search@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $this->postJson('/api/v1/products', validProductPayload($catalog, [
        'name' => 'Alpha Gadget',
        'sku' => 'AG-001',
        'barcode' => '1111111111111',
    ]), $headers)->assertCreated();

    $this->postJson('/api/v1/products', validProductPayload($catalog, [
        'name' => 'Beta Gadget',
        'sku' => 'BG-001',
        'barcode' => '2222222222222',
    ]), $headers)->assertCreated();

    $this->getJson('/api/v1/products?search=Alpha', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Alpha Gadget');

    $this->getJson('/api/v1/products?search=BG-001', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sku', 'BG-001');

    $this->getJson('/api/v1/products?search=2222222222222', $headers)
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.barcode', '2222222222222');
});

test('product index supports sorting by name', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-sort@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $this->postJson('/api/v1/products', validProductPayload($catalog, ['name' => 'Zulu Product', 'sku' => 'Z-001']), $headers)->assertCreated();
    $this->postJson('/api/v1/products', validProductPayload($catalog, ['name' => 'Alpha Product', 'sku' => 'A-001']), $headers)->assertCreated();

    $response = $this->getJson('/api/v1/products?sort=name', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Alpha Product')
        ->assertJsonPath('data.1.name', 'Zulu Product');
});

test('product store creates a product', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $response = $this->postJson('/api/v1/products', validProductPayload($catalog, [
        'sku' => 'WM-STORE',
        'barcode' => '1234567890123',
    ]), $headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Wireless Mouse')
        ->assertJsonPath('data.sku', 'WM-STORE')
        ->assertJsonPath('data.reorder_point', 25)
        ->assertJsonPath('data.is_active', true);

    $this->assertDatabaseHas('products', [
        'organization_id' => $org['organization_id'],
        'sku' => 'WM-STORE',
        'reorder_point' => 25,
    ]);
});

test('product store enforces sku uniqueness per organization', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-sku-uniq@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $this->postJson('/api/v1/products', validProductPayload($catalog, ['sku' => 'SHARED-SKU']), $headers)->assertCreated();

    $this->postJson('/api/v1/products', validProductPayload($catalog, [
        'name' => 'Duplicate SKU Product',
        'sku' => 'SHARED-SKU',
        'barcode' => '9999999999999',
    ]), $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['sku']);
});

test('product store allows the same sku in different organizations', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'prod-sku-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'prod-sku-b@acme.test']);

    $catalogA = createProductCatalog($this, $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']));
    $catalogB = createProductCatalog($this, $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']));

    $this->postJson(
        '/api/v1/products',
        validProductPayload($catalogA, ['sku' => 'SHARED-SKU']),
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->postJson(
        '/api/v1/products',
        validProductPayload($catalogB, ['sku' => 'SHARED-SKU']),
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertCreated();
});

test('product store rejects category_id from another organization', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'prod-fk-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'prod-fk-b@acme.test']);

    $catalogA = createProductCatalog($this, $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']));
    $catalogB = createProductCatalog($this, $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']));

    $this->postJson(
        '/api/v1/products',
        validProductPayload($catalogA, ['category_id' => $catalogB['category_id']]),
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category_id']);
});

test('product show returns a single product', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-show@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $created = $this->postJson('/api/v1/products', validProductPayload($catalog), $headers)->assertCreated();

    $response = $this->getJson('/api/v1/products/'.$created->json('data.id'), $headers);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Wireless Mouse');
});

test('product update changes product attributes', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-update@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $created = $this->postJson('/api/v1/products', validProductPayload($catalog), $headers)->assertCreated();

    $response = $this->putJson('/api/v1/products/'.$created->json('data.id'), [
        'name' => 'Updated Mouse',
        'selling_price' => 24.99,
        'reorder_point' => 10,
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Mouse')
        ->assertJsonPath('data.selling_price', '24.99')
        ->assertJsonPath('data.reorder_point', 10);
});

test('product destroy deletes a product', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-delete@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $created = $this->postJson('/api/v1/products', validProductPayload($catalog), $headers)->assertCreated();

    $this->deleteJson('/api/v1/products/'.$created->json('data.id'), [], $headers)
        ->assertNoContent();

    $this->assertDatabaseMissing('products', [
        'id' => $created->json('data.id'),
    ]);
});

test('viewer cannot create a product', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-owner@acme.test']);
    $catalog = createProductCatalog($this, $this->organizationHeaders($org['token'], $org['organization_id']));

    $viewer = User::factory()->create(['email' => 'prod-viewer@acme.test']);
    $viewer->organizations()->attach($org['organization_id'], ['role' => 'Viewer']);

    setPermissionsTeamId($org['organization_id']);
    $viewer->assignRole('Viewer');

    $viewerLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'prod-viewer@acme.test',
        'password' => 'password',
    ])->assertOk();

    $headers = $this->organizationContextHeaders(
        $viewerLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->postJson('/api/v1/products', validProductPayload($catalog, ['sku' => 'VIEWER-SKU']), $headers)
        ->assertForbidden();
});

test('org owner can create a product', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'prod-owner-create@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createProductCatalog($this, $headers);

    $this->postJson('/api/v1/products', validProductPayload($catalog, ['sku' => 'OWNER-SKU']), $headers)
        ->assertCreated()
        ->assertJsonPath('data.sku', 'OWNER-SKU');
});

test('organization cannot view another organizations product', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'prod-org-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'prod-org-b@acme.test']);

    $catalogA = createProductCatalog($this, $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']));

    $product = $this->postJson(
        '/api/v1/products',
        validProductPayload($catalogA, ['sku' => 'ORG-A-SKU']),
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->getJson(
        '/api/v1/products/'.$product->json('data.id'),
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertNotFound();
});

test('organization cannot update another organizations product', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'prod-upd-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'prod-upd-b@acme.test']);

    $catalogA = createProductCatalog($this, $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']));

    $product = $this->postJson(
        '/api/v1/products',
        validProductPayload($catalogA, ['sku' => 'PROTECTED-SKU']),
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->putJson(
        '/api/v1/products/'.$product->json('data.id'),
        ['name' => 'Hijacked'],
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertNotFound();

    $this->assertDatabaseHas('products', [
        'id' => $product->json('data.id'),
        'name' => 'Wireless Mouse',
    ]);
});

test('product index never leaks records from another organization', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'prod-leak-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'prod-leak-b@acme.test']);

    $headersA = $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']);
    $catalogA = createProductCatalog($this, $headersA);

    $this->postJson('/api/v1/products', validProductPayload($catalogA, ['sku' => 'LEAK-A']), $headersA)->assertCreated();

    $categoryB = Category::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Category',
        'slug' => 'org-b-category',
    ]);

    $unitB = Unit::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Unit',
        'symbol' => 'ob',
    ]);

    Product::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'category_id' => $categoryB->id,
        'unit_id' => $unitB->id,
        'name' => 'Org B Only',
        'sku' => 'LEAK-B',
        'cost_price' => 1,
        'selling_price' => 2,
        'tax_rate' => 0,
        'is_active' => true,
    ]);

    $response = $this->getJson('/api/v1/products', $headersA);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.sku', 'LEAK-A');
});
