<?php

use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('unit index lists units for the current organization', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-index@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/units', ['name' => 'Kilogram', 'symbol' => 'kg'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/units', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Kilogram')
        ->assertJsonStructure([
            'data' => [['id', 'name', 'symbol', 'organization_id']],
            'meta' => ['pagination' => ['current_page', 'per_page', 'total', 'last_page']],
        ]);
});

test('unit index supports filter by name', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-filter@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)->assertCreated();
    $this->postJson('/api/v1/units', ['name' => 'Box', 'symbol' => 'box'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/units?filter[name]=Piece', $headers);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Piece');
});

test('unit index supports sorting by name', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-sort@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/units', ['name' => 'Zulu Unit', 'symbol' => 'zu'], $headers)->assertCreated();
    $this->postJson('/api/v1/units', ['name' => 'Alpha Unit', 'symbol' => 'au'], $headers)->assertCreated();

    $response = $this->getJson('/api/v1/units?sort=name', $headers);

    $response->assertOk()
        ->assertJsonPath('data.0.name', 'Alpha Unit')
        ->assertJsonPath('data.1.name', 'Zulu Unit');
});

test('unit store creates a unit', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-store@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $response = $this->postJson('/api/v1/units', [
        'name' => 'Kilogram',
        'symbol' => 'kg',
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Kilogram')
        ->assertJsonPath('data.symbol', 'kg');

    $this->assertDatabaseHas('units', [
        'organization_id' => $org['organization_id'],
        'name' => 'Kilogram',
        'symbol' => 'kg',
    ]);
});

test('unit show returns a single unit', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-show@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/units', ['name' => 'Piece', 'symbol' => 'pcs'], $headers)
        ->assertCreated();

    $response = $this->getJson('/api/v1/units/'.$created->json('data.id'), $headers);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Piece')
        ->assertJsonPath('data.symbol', 'pcs');
});

test('unit update changes unit attributes', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-update@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/units', ['name' => 'Old Unit', 'symbol' => 'old'], $headers)
        ->assertCreated();

    $response = $this->putJson('/api/v1/units/'.$created->json('data.id'), [
        'name' => 'New Unit',
        'symbol' => 'new',
    ], $headers);

    $response->assertOk()
        ->assertJsonPath('data.name', 'New Unit')
        ->assertJsonPath('data.symbol', 'new');
});

test('unit destroy deletes a unit', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-delete@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $created = $this->postJson('/api/v1/units', ['name' => 'Disposable Unit', 'symbol' => 'du'], $headers)
        ->assertCreated();

    $this->deleteJson('/api/v1/units/'.$created->json('data.id'), [], $headers)
        ->assertNoContent();

    $this->assertDatabaseMissing('units', [
        'id' => $created->json('data.id'),
    ]);
});

test('viewer cannot create a unit', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-owner@acme.test']);

    $viewer = User::factory()->create(['email' => 'unit-viewer@acme.test']);
    $viewer->organizations()->attach($org['organization_id'], ['role' => 'Viewer']);

    setPermissionsTeamId($org['organization_id']);
    $viewer->assignRole('Viewer');

    $viewerLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'unit-viewer@acme.test',
        'password' => 'password',
    ])->assertOk();

    $headers = $this->organizationHeaders(
        $viewerLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->postJson('/api/v1/units', ['name' => 'Blocked Unit', 'symbol' => 'bu'], $headers)
        ->assertForbidden();
});

test('org owner can create a unit', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'unit-owner-create@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/units', ['name' => 'Box', 'symbol' => 'box'], $headers)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Box');
});

test('organization cannot view another organizations unit', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'unit-org-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'unit-org-b@acme.test']);

    $unit = $this->postJson(
        '/api/v1/units',
        ['name' => 'Org A Unit', 'symbol' => 'oa'],
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->getJson(
        '/api/v1/units/'.$unit->json('data.id'),
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertNotFound();
});

test('organization cannot update another organizations unit', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'unit-upd-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'unit-upd-b@acme.test']);

    $unit = $this->postJson(
        '/api/v1/units',
        ['name' => 'Protected Unit', 'symbol' => 'pu'],
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )->assertCreated();

    $this->putJson(
        '/api/v1/units/'.$unit->json('data.id'),
        ['name' => 'Hijacked'],
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertNotFound();

    $this->assertDatabaseHas('units', [
        'id' => $unit->json('data.id'),
        'name' => 'Protected Unit',
    ]);
});

test('unit index never leaks records from another organization', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'unit-leak-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'unit-leak-b@acme.test']);

    $headersA = $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']);

    $this->postJson('/api/v1/units', ['name' => 'Org A Only', 'symbol' => 'ao'], $headersA)->assertCreated();

    Unit::withoutOrganizationScope()->create([
        'organization_id' => $orgB['organization_id'],
        'name' => 'Org B Only',
        'symbol' => 'bo',
    ]);

    $response = $this->getJson('/api/v1/units', $headersA);

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Org A Only');
});
