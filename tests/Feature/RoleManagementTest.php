<?php

use App\Models\Role;
use App\Models\User;
use App\Permission\PermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('org owner can list roles and permission catalog', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'roles-list@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->getJson('/api/v1/roles', $headers)
        ->assertOk()
        ->assertJsonStructure(['data' => [['id', 'name', 'permissions', 'users_count']]]);

    $this->getJson('/api/v1/roles/permissions', $headers)
        ->assertOk()
        ->assertJsonPath('data.Inventory.0', 'inventory.view');
});

test('org owner can create and update a custom role', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'roles-crud@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $create = $this->postJson('/api/v1/roles', [
        'name' => 'Receiving Clerk',
        'description' => 'Can receive purchase orders only.',
        'permissions' => [
            'inventory.view',
            'orders.purchase.view',
            'orders.purchase.receive',
        ],
    ], $headers)
        ->assertCreated()
        ->assertJsonPath('data.name', 'Receiving Clerk');

    $roleId = $create->json('data.id');

    $this->patchJson("/api/v1/roles/{$roleId}", [
        'permissions' => [
            'inventory.view',
            'orders.purchase.view',
        ],
    ], $headers)
        ->assertOk()
        ->assertJsonCount(2, 'data.permissions');
});

test('org owner can customize org owner role permissions', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'roles-owner-custom@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $orgOwnerRole = Role::query()
        ->where('organization_id', $org['organization_id'])
        ->where('name', PermissionCatalog::ORG_OWNER_ROLE)
        ->firstOrFail();

    $this->patchJson("/api/v1/roles/{$orgOwnerRole->id}", [
        'permissions' => ['inventory.view', 'settings.view'],
    ], $headers)
        ->assertOk()
        ->assertJsonCount(2, 'data.permissions');
});

test('protected system owner role cannot be modified or deleted', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'roles-protected@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $systemOwnerRole = Role::query()
        ->where('organization_id', $org['organization_id'])
        ->where('name', PermissionCatalog::SYSTEM_OWNER_ROLE)
        ->firstOrFail();

    expect($systemOwnerRole->is_protected)->toBeTrue();

    $this->patchJson("/api/v1/roles/{$systemOwnerRole->id}", [
        'permissions' => ['inventory.view'],
    ], $headers)->assertForbidden();

    $this->deleteJson("/api/v1/roles/{$systemOwnerRole->id}", [], $headers)->assertForbidden();
});

test('role assigned to users cannot be deleted', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'roles-delete-block@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/users', [
        'name' => 'Assigned Viewer',
        'email' => 'assigned-viewer@acme.test',
        'password' => 'password123',
        'role' => 'Viewer',
    ], $headers)->assertCreated();

    $viewerRole = Role::query()
        ->where('organization_id', $org['organization_id'])
        ->where('name', 'Viewer')
        ->firstOrFail();

    $this->deleteJson("/api/v1/roles/{$viewerRole->id}", [], $headers)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['role']);
});

test('manager without settings.manage_roles cannot manage roles', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'roles-manager-block@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/users', [
        'name' => 'Role Manager',
        'email' => 'role-manager@acme.test',
        'password' => 'password123',
        'role' => 'Manager',
    ], $headers)->assertCreated();

    $managerLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'role-manager@acme.test',
        'password' => 'password123',
    ])->assertOk();

    $managerHeaders = $this->organizationContextHeaders(
        $managerLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->getJson('/api/v1/roles', $managerHeaders)->assertForbidden();
    $this->postJson('/api/v1/roles', [
        'name' => 'Blocked Role',
        'permissions' => ['inventory.view'],
    ], $managerHeaders)->assertForbidden();
});

test('register seeds system owner role for each organization', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'roles-seed@acme.test']);

    $this->assertDatabaseHas('roles', [
        'organization_id' => $org['organization_id'],
        'name' => PermissionCatalog::SYSTEM_OWNER_ROLE,
        'is_protected' => true,
    ]);

    $owner = User::query()->where('email', 'roles-seed@acme.test')->firstOrFail();

    $this->assertDatabaseHas('model_has_roles', [
        'model_type' => User::class,
        'model_id' => $owner->id,
        'organization_id' => $org['organization_id'],
    ]);
});
