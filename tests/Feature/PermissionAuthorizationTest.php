<?php

use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('viewer cannot access endpoint gated by products.create', function () {
    $registerResponse = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'owner@acme.test',
    ]))->assertCreated();

    $organizationId = $registerResponse->json('data.organizations.0.id');

    $viewer = User::factory()->create([
        'email' => 'viewer@acme.test',
    ]);

    $viewer->organizations()->attach($organizationId, ['role' => 'Viewer']);

    setPermissionsTeamId($organizationId);
    $viewer->assignRole('Viewer');

    $viewerLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'viewer@acme.test',
        'password' => 'password',
    ])->assertOk();

    $this->postJson('/api/v1/products/authorization-probe', [], [
        'Authorization' => 'Bearer '.$viewerLogin->json('data.token.access_token'),
        'X-Organization-Id' => $organizationId,
    ])
        ->assertForbidden()
        ->assertJsonStructure(['message', 'errors']);
});

test('org owner can access endpoint gated by products.create', function () {
    $registerResponse = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'owner-products@acme.test',
    ]))->assertCreated();

    $organizationId = $registerResponse->json('data.organizations.0.id');
    $accessToken = $registerResponse->json('data.token.access_token');

    $this->postJson('/api/v1/products/authorization-probe', [], [
        'Authorization' => 'Bearer '.$accessToken,
        'X-Organization-Id' => $organizationId,
    ])
        ->assertCreated()
        ->assertJsonPath('data.created', true);
});

test('register seeds default organization roles and permissions', function () {
    $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'seed-check@acme.test',
    ]))->assertCreated();

    foreach (RolesAndPermissionsSeeder::permissions() as $permission) {
        $this->assertDatabaseHas('permissions', [
            'name' => $permission,
            'guard_name' => RolesAndPermissionsSeeder::GUARD,
        ]);
    }

    $organizationId = \App\Models\Organization::query()->value('id');

    foreach (array_keys(RolesAndPermissionsSeeder::rolePermissionMap()) as $roleName) {
        $this->assertDatabaseHas('roles', [
            'name' => $roleName,
            'guard_name' => RolesAndPermissionsSeeder::GUARD,
            'organization_id' => $organizationId,
        ]);
    }

    $this->assertDatabaseHas('model_has_roles', [
        'model_type' => User::class,
        'organization_id' => $organizationId,
    ]);
});

test('only org owner can update organization settings via policy', function () {
    $registerResponse = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'policy-owner@acme.test',
    ]))->assertCreated();

    $organization = \App\Models\Organization::query()->first();
    $owner = User::query()->where('email', 'policy-owner@acme.test')->first();

    $viewer = User::factory()->create(['email' => 'policy-viewer@acme.test']);
    $viewer->organizations()->attach($organization->id, ['role' => 'Viewer']);

    setPermissionsTeamId($organization->id);
    $viewer->assignRole('Viewer');

    setPermissionsTeamId($organization->id);
    expect($owner->can('update', $organization))->toBeTrue();

    setPermissionsTeamId($organization->id);
    expect($viewer->can('update', $organization))->toBeFalse();
});
