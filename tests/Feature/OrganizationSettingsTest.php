<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('org owner can view current organization settings', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'org-settings-owner@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->getJson('/api/v1/organization', $headers)
        ->assertOk()
        ->assertJsonPath('data.name', $org['response']->json('data.organizations.0.name'))
        ->assertJsonPath('data.users_count', 1);
});

test('org owner can update organization profile', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'org-update-owner@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->patchJson('/api/v1/organization', [
        'name' => 'Updated Warehouse Co',
        'email' => 'contact@updated.test',
        'phone' => '+15550101010',
    ], $headers)
        ->assertOk()
        ->assertJsonPath('data.name', 'Updated Warehouse Co')
        ->assertJsonPath('data.email', 'contact@updated.test')
        ->assertJsonPath('data.phone', '+15550101010')
        ->assertJsonPath('data.slug', 'updated-warehouse-co');

    $this->assertDatabaseHas('organizations', [
        'id' => $org['organization_id'],
        'name' => 'Updated Warehouse Co',
        'slug' => 'updated-warehouse-co',
    ]);
});

test('admin with settings permissions can view and update organization profile', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'org-admin-allowed@acme.test',
    ]))->assertCreated();

    $organizationId = $register->json('data.organizations.0.id');
    $headers = $this->organizationHeaders($register->json('data.token.access_token'), $organizationId);

    $this->postJson('/api/v1/users', [
        'name' => 'Org Admin',
        'email' => 'org-admin@acme.test',
        'password' => 'password123',
        'role' => 'Admin',
    ], $headers)->assertCreated();

    $adminLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'org-admin@acme.test',
        'password' => 'password123',
    ])->assertOk();

    $adminHeaders = $this->organizationContextHeaders(
        $adminLogin->json('data.token.access_token'),
        $organizationId,
    );

    $this->getJson('/api/v1/organization', $adminHeaders)->assertOk();

    $this->patchJson('/api/v1/organization', [
        'name' => 'Admin Updated Co',
    ], $adminHeaders)
        ->assertOk()
        ->assertJsonPath('data.name', 'Admin Updated Co');
});

test('manager can view but cannot update organization settings', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'org-manager-blocked@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/users', [
        'name' => 'Org Manager',
        'email' => 'org-manager@acme.test',
        'password' => 'password123',
        'role' => 'Manager',
    ], $headers)->assertCreated();

    $managerLogin = $this->postJson('/api/v1/auth/login', [
        'email' => 'org-manager@acme.test',
        'password' => 'password123',
    ])->assertOk()
        ->assertJsonPath('data.user.email', 'org-manager@acme.test');

    $managerHeaders = $this->organizationContextHeaders(
        $managerLogin->json('data.token.access_token'),
        $org['organization_id'],
    );

    $this->getJson('/api/v1/organization', $managerHeaders)->assertOk();
    $this->patchJson('/api/v1/organization', ['name' => 'Blocked'], $managerHeaders)->assertForbidden();
});
