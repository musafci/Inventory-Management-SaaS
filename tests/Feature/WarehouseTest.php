<?php

use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('warehouse index lists warehouses for the current organization', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-index@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/warehouses', [
        'name' => 'Main Warehouse',
        'address' => '123 Storage Lane',
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/warehouses', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Main Warehouse')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'address', 'is_default', 'organization_id']],
            'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']],
        ]);
});

test('warehouse index supports filter by name', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-filter@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/warehouses', ['name' => 'North Depot'], $headers)->assertCreated();
    $this->postJson('/api/v1/warehouses', ['name' => 'South Depot'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/warehouses?filter[name]=North', $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'North Depot');
});

test('warehouse index supports sorting by name', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-sort@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/warehouses', ['name' => 'Zulu Warehouse'], $headers)->assertCreated();
    $this->postJson('/api/v1/warehouses', ['name' => 'Alpha Warehouse'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/warehouses?sort=name', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Alpha Warehouse')
        ->assertJsonPath('data.1.name', 'Zulu Warehouse');
});

test('warehouse store creates a warehouse and marks the first one as default', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $response = $this->postJson('/api/v1/warehouses', [
        'name' => 'Primary Warehouse',
        'address' => '1 Industrial Park',
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Primary Warehouse')
        ->assertJsonPath('data.is_default', true);

    $this->assertDatabaseHas('warehouses', [
        'organization_id' => $org['organization_id'],
        'name' => 'Primary Warehouse',
        'is_default' => true,
    ]);
});

test('warehouse store can set a new default warehouse', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-default@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $first = $this->postJson('/api/v1/warehouses', ['name' => 'First'], $headers)->assertCreated();
    $second = $this->postJson('/api/v1/warehouses', [
        'name' => 'Second',
        'is_default' => true,
    ], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/warehouses', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.is_default', false)
        ->assertJsonPath('data.1.is_default', true);

    $this->assertDatabaseHas('warehouses', [
        'id' => $first->json('data.id'),
        'is_default' => false,
    ]);

    $this->assertDatabaseHas('warehouses', [
        'id' => $second->json('data.id'),
        'is_default' => true,
    ]);
});

test('warehouse show returns a single warehouse', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-show@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/warehouses', ['name' => 'Showcase Warehouse'], $headers)
        ->assertCreated();

    $response = $this->getJson('/api/v1/warehouses/'.$created->json('data.id'), $headers);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Showcase Warehouse');
});

test('warehouse update changes warehouse attributes', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-update@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/warehouses', ['name' => 'Old Name'], $headers)
        ->assertCreated();

    $response = $this->putJson('/api/v1/warehouses/'.$created->json('data.id'), [
        'name' => 'New Name',
        'address' => 'Updated Address',
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Name')
        ->assertJsonPath('data.address', 'Updated Address');
});

test('warehouse destroy deletes a warehouse', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-delete@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/warehouses', ['name' => 'Disposable Warehouse'], $headers)
        ->assertCreated();

    $this->deleteJson('/api/v1/warehouses/'.$created->json('data.id'), [], $headers)
        ->assertNoContent();

    $this->assertDatabaseMissing('warehouses', [
        'id' => $created->json('data.id'),
    ]);
});

test('viewer cannot create a warehouse', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-owner@acme.test']);

    $viewer = User::factory()->create(['email' => 'wh-viewer@acme.test']);
    $viewer->organizations()->attach($org['organization_id'], ['role' => 'Viewer']);

    setPermissionsTeamId($org['organization_id']);
    $viewer->assignRole('Viewer');

    $viewerLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'wh-viewer@acme.test',
        'password' => 'password',
    ])->assertOk();

    $headers = $this->organizationHeaders(
        $viewerLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->postJson('/api/v1/warehouses', ['name' => 'Blocked Warehouse'], $headers)
        ->assertForbidden();
});

test('org owner can create a warehouse', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'wh-owner-create@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/warehouses', ['name' => 'Owner Warehouse'], $headers)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Owner Warehouse');
});

test('organization cannot view another organizations warehouse', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'wh-org-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'wh-org-b@acme.test']);

    $warehouse = $this->postJson(
        '/api/v1/warehouses',
        ['name' => 'Org A Warehouse'],
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->getJson(
        '/api/v1/warehouses/'.$warehouse->json('data.id'),
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertNotFound();
});

test('organization cannot update another organizations warehouse', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'wh-upd-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'wh-upd-b@acme.test']);

    $warehouse = $this->postJson(
        '/api/v1/warehouses',
        ['name' => 'Protected Warehouse'],
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->putJson(
        '/api/v1/warehouses/'.$warehouse->json('data.id'),
        ['name' => 'Hijacked'],
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertNotFound();

    $this->assertDatabaseHas('warehouses', [
        'id' => $warehouse->json('data.id'),
        'name' => 'Protected Warehouse',
    ]);
});

test('warehouse index never leaks records from another organization', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'wh-leak-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'wh-leak-b@acme.test']);

    $headersA = $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']);

    $this->postJson('/api/v1/warehouses', ['name' => 'Org A Only'], $headersA)->assertCreated();

    Warehouse::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Only',
        'is_default' => true,
    ]);

    $response = $this->getJson('/api/v1/warehouses', $headersA);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Org A Only');
});
