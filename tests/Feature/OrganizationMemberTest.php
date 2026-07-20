<?php

use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('org owner can list organization members', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'members-owner@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->getJson('/api/v1/users', $headers)
        ->assertOk()
        ->assertJsonPath('data.0.email', 'members-owner@acme.test')
        ->assertJsonPath('data.0.role', 'Org Owner');
});

test('org owner can invite a member', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'invite-owner@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/users', [
        'name' => 'New Manager',
        'email' => 'manager@acme.test',
        'password' => 'password123',
        'role' => 'Manager',
    ], $headers)->assertCreated()
        ->assertJsonPath('data.email', 'manager@acme.test')
        ->assertJsonPath('data.role', 'Manager');

    $this->assertDatabaseHas('organization_user', [
        'organization_id' => $org['organization_id'],
        'role' => 'Manager',
    ]);
});

test('viewer cannot manage organization members', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'member-policy-owner@acme.test',
    ]))->assertCreated();

    $organizationId = $register->json('data.organizations.0.id');

    $viewer = User::factory()->create(['email' => 'viewer-members@acme.test']);
    $viewer->organizations()->attach($organizationId, ['role' => 'Viewer']);
    setPermissionsTeamId($organizationId);
    $viewer->assignRole('Viewer');

    $login = $this->postJson('/api/v1/auth/login', [
        'email' => 'viewer-members@acme.test',
        'password' => 'password',
    ])->assertOk();

    $this->getJson('/api/v1/users', $this->organizationHeaders(
        $login->json('data.token.access_token'),
        $organizationId,
    ))->assertForbidden();
});

test('admin role is seeded for new organizations', function () {
    $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'admin-role@acme.test',
    ]))->assertCreated();

    $organizationId = Organization::query()->value('id');

    $this->assertDatabaseHas('roles', [
        'name' => 'Admin',
        'organization_id' => $organizationId,
        'guard_name' => RolesAndPermissionsSeeder::GUARD,
    ]);
});
