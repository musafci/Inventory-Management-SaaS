<?php

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('category index lists categories for the current organization', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-index@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/categories', ['name' => 'Electronics'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/categories', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Electronics')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'slug', 'parent_id', 'organization_id']],
            'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']],
        ]);
});

test('category index supports filter by name', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-filter@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/categories', ['name' => 'Office Supplies'], $headers)->assertCreated();
    $this->postJson('/api/v1/categories', ['name' => 'Warehouse Gear'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/categories?filter[name]=Office', $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Office Supplies');
});

test('category index supports sorting by name', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-sort@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/categories', ['name' => 'Zulu Category'], $headers)->assertCreated();
    $this->postJson('/api/v1/categories', ['name' => 'Alpha Category'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/categories?sort=name', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Alpha Category')
        ->assertJsonPath('data.1.name', 'Zulu Category');
});

test('category store creates a category with an auto-generated slug', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $response = $this->postJson('/api/v1/categories', ['name' => 'Home & Garden'], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Home & Garden')
        ->assertJsonPath('data.slug', 'home-garden')
        ->assertJsonPath('data.parent_id', null);

    $this->assertDatabaseHas('categories', [
        'organization_id' => $org['organization_id'],
        'name' => 'Home & Garden',
        'slug' => 'home-garden',
    ]);
});

test('category store can create a nested category', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-nest@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $parent = $this->postJson('/api/v1/categories', ['name' => 'Electronics'], $headers)->assertCreated();

    $response = $this->postJson('/api/v1/categories', [
        'name' => 'Phones',
        'parent_id' => $parent->json('data.id'),
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Phones')
        ->assertJsonPath('data.parent_id', $parent->json('data.id'));
});

test('category show returns a single category', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-show@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/categories', ['name' => 'Showcase Category'], $headers)
        ->assertCreated();

    $response = $this->getJson('/api/v1/categories/'.$created->json('data.id'), $headers);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Showcase Category');
});

test('category update changes category attributes', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-update@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/categories', ['name' => 'Old Category'], $headers)
        ->assertCreated();

    $response = $this->putJson('/api/v1/categories/'.$created->json('data.id'), [
        'name' => 'New Category',
        'slug' => 'new-category',
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Category')
        ->assertJsonPath('data.slug', 'new-category');
});

test('category update rejects nesting under a descendant', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-cycle@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $parent = $this->postJson('/api/v1/categories', ['name' => 'Parent'], $headers)->assertCreated();
    $child = $this->postJson('/api/v1/categories', [
        'name' => 'Child',
        'parent_id' => $parent->json('data.id'),
    ], $headers)->assertCreated();

    $this->putJson('/api/v1/categories/'.$parent->json('data.id'), [
        'parent_id' => $child->json('data.id'),
    ], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['parent_id']);
});

test('category destroy deletes a category without children', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-delete@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/categories', ['name' => 'Disposable Category'], $headers)
        ->assertCreated();

    $this->deleteJson('/api/v1/categories/'.$created->json('data.id'), [], $headers)
        ->assertNoContent();

    $this->assertDatabaseMissing('categories', [
        'id' => $created->json('data.id'),
    ]);
});

test('category destroy rejects deleting a category with children', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-delete-child@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $parent = $this->postJson('/api/v1/categories', ['name' => 'Parent'], $headers)->assertCreated();
    $this->postJson('/api/v1/categories', [
        'name' => 'Child',
        'parent_id' => $parent->json('data.id'),
    ], $headers)->assertCreated();

    $this->deleteJson('/api/v1/categories/'.$parent->json('data.id'), [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['category']);
});

test('viewer cannot create a category', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-owner@acme.test']);

    $viewer = User::factory()->create(['email' => 'cat-viewer@acme.test']);
    $viewer->organizations()->attach($org['organization_id'], ['role' => 'Viewer']);

    setPermissionsTeamId($org['organization_id']);
    $viewer->assignRole('Viewer');

    $viewerLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'cat-viewer@acme.test',
        'password' => 'password',
    ])->assertOk();

    $headers = $this->organizationHeaders(
        $viewerLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->postJson('/api/v1/categories', ['name' => 'Blocked Category'], $headers)
        ->assertForbidden();
});

test('org owner can create a category', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cat-owner-create@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/categories', ['name' => 'Owner Category'], $headers)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Owner Category');
});

test('organization cannot view another organizations category', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'cat-org-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'cat-org-b@acme.test']);

    $category = $this->postJson(
        '/api/v1/categories',
        ['name' => 'Org A Category'],
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->getJson(
        '/api/v1/categories/'.$category->json('data.id'),
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertNotFound();
});

test('organization cannot update another organizations category', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'cat-upd-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'cat-upd-b@acme.test']);

    $category = $this->postJson(
        '/api/v1/categories',
        ['name' => 'Protected Category'],
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->putJson(
        '/api/v1/categories/'.$category->json('data.id'),
        ['name' => 'Hijacked'],
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertNotFound();

    $this->assertDatabaseHas('categories', [
        'id' => $category->json('data.id'),
        'name' => 'Protected Category',
    ]);
});

test('category index never leaks records from another organization', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'cat-leak-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'cat-leak-b@acme.test']);

    $headersA = $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']);

    $this->postJson('/api/v1/categories', ['name' => 'Org A Only'], $headersA)->assertCreated();

    Category::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Only',
        'slug' => 'org-b-only',
    ]);

    $response = $this->getJson('/api/v1/categories', $headersA);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Org A Only');
});
