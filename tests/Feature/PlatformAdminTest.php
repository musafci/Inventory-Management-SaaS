<?php

use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('platform admin can login and list organizations', function () {
    $admin = PlatformAdmin::factory()->create([
        'email' => 'platform@acme.test',
        'password' => 'password123',
    ]);

    $login = $this->postJson('/api/platform/v1/auth/login', [
        'email' => 'platform@acme.test',
        'password' => 'password123',
    ])->assertOk();

    $token = $login->json('data.token.access_token');

    $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'organization_name' => 'Platform Visible Org',
        'email' => 'platform-org-owner@acme.test',
    ]))->assertCreated();

    $this->getJson('/api/platform/v1/organizations', [
        'Authorization' => 'Bearer '.$token,
    ])->assertOk()
        ->assertJsonPath('data.0.name', 'Platform Visible Org');
});

test('platform admin can suspend an organization', function () {
    PlatformAdmin::factory()->create([
        'email' => 'platform-admin@acme.test',
        'password' => 'password123',
    ]);

    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'suspend-target@acme.test',
    ]))->assertCreated();

    $organizationId = $register->json('data.organizations.0.id');

    Passport::actingAs(
        PlatformAdmin::query()->where('email', 'platform-admin@acme.test')->first(),
        [],
        'platform',
    );

    $this->patchJson("/api/platform/v1/organizations/{$organizationId}", [
        'status' => 'suspended',
    ])->assertOk()
        ->assertJsonPath('data.status', 'suspended');
});

test('tenant users cannot access platform routes', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'not-platform@acme.test']);

    $this->getJson('/api/platform/v1/organizations', [
        'Authorization' => 'Bearer '.$org['token'],
    ])->assertUnauthorized();
});
