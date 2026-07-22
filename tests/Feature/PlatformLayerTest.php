<?php

use App\Models\Organization;
use App\Models\Plan;
use App\Models\PlatformAdmin;
use App\Models\User;
use App\Services\OrganizationSubscriptionService;
use App\Enums\SubscriptionStatus;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('suspended organization is blocked on tenant api', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'suspend-block@acme.test']);

    PlatformAdmin::factory()->create(['email' => 'suspend-admin@acme.test', 'password' => 'password123']);

    Passport::actingAs(
        PlatformAdmin::query()->where('email', 'suspend-admin@acme.test')->first(),
        [],
        'platform',
    );

    $this->patchJson("/api/platform/v1/organizations/{$org['organization_id']}", [
        'status' => 'suspended',
    ])->assertOk();

    $this->getJson('/api/v1/products', $this->organizationHeaders($org['token'], $org['organization_id']))
        ->assertForbidden()
        ->assertJsonPath('message', 'This organization has been suspended.');
});

test('platform admin token cannot access tenant routes', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'guard-tenant@acme.test']);
    $admin = PlatformAdmin::factory()->create(['email' => 'guard-admin@acme.test', 'password' => 'password123']);

    Passport::actingAs($admin, [], 'platform');

    $this->getJson('/api/v1/products', [
        'X-Organization-Id' => (string) $org['organization_id'],
    ])->assertUnauthorized();
});

test('plan limit blocks warehouse creation on growth trial plan', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'plan-limit@acme.test',
    ]))->assertCreated();

    $organizationId = (int) $register->json('data.organizations.0.id');
    $token = $register->json('data.token.access_token');
    $headers = $this->organizationHeaders($token, $organizationId);

    foreach (['First', 'Second', 'Third', 'Fourth'] as $index => $label) {
        $this->postJson('/api/v1/warehouses', [
            'name' => "{$label} Warehouse",
            'address' => ($index + 1).' Main St',
        ], $headers)->assertCreated();
    }

    $this->postJson('/api/v1/warehouses', [
        'name' => 'Fifth Warehouse',
        'address' => '5 Main St',
    ], $headers)
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Upgrade required'));
});

test('platform admin can manage subscription plans', function () {
    $admin = PlatformAdmin::factory()->create(['email' => 'sub-admin@acme.test', 'password' => 'password123']);
    Passport::actingAs($admin, [], 'platform');

    $org = $this->registerOrganizationWithOwner(['email' => 'sub-org@acme.test']);
    $businessPlan = Plan::query()->where('slug', 'business')->firstOrFail();

    $this->patchJson("/api/platform/v1/organizations/{$org['organization_id']}/subscription", [
        'plan_id' => $businessPlan->id,
        'status' => 'active',
    ])->assertOk()
        ->assertJsonPath('data.plan.slug', 'business');

    $organization = Organization::query()->findOrFail($org['organization_id']);
    expect($organization->plan)->toBe('business');
});

test('platform admin can add support notes and toggle feature flags', function () {
    $admin = PlatformAdmin::factory()->create(['email' => 'flags-admin@acme.test', 'password' => 'password123']);
    Passport::actingAs($admin, [], 'platform');

    $org = $this->registerOrganizationWithOwner(['email' => 'flags-org@acme.test']);

    $this->postJson("/api/platform/v1/organizations/{$org['organization_id']}/support-notes", [
        'note' => 'Customer requested billing review.',
    ])->assertCreated()
        ->assertJsonPath('data.note', 'Customer requested billing review.');

    $flags = $this->getJson("/api/platform/v1/organizations/{$org['organization_id']}/feature-flags")
        ->assertOk()
        ->json('data');

    expect($flags)->not->toBeEmpty();

    $flagId = $flags[0]['id'];

    $this->patchJson("/api/platform/v1/organizations/{$org['organization_id']}/feature-flags/{$flagId}", [
        'enabled' => ! $flags[0]['enabled'],
    ])->assertOk();
});

test('platform admin can impersonate a tenant user with logging', function () {
    $admin = PlatformAdmin::factory()->create(['email' => 'imp-admin@acme.test', 'password' => 'password123']);
    Passport::actingAs($admin, [], 'platform');

    $org = $this->registerOrganizationWithOwner(['email' => 'imp-owner@acme.test']);
    $userId = (int) $org['response']->json('data.user.id');

    $start = $this->postJson("/api/platform/v1/organizations/{$org['organization_id']}/impersonate", [
        'user_id' => $userId,
        'reason' => 'Support ticket #42 — login issue investigation',
    ])->assertCreated();

    $impersonationToken = $start->json('data.token.access_token');

    $this->getJson('/api/v1/auth/me', array_merge(
        $this->organizationHeaders($impersonationToken, $org['organization_id']),
    ))->assertOk()
        ->assertJsonPath('data.impersonation.active', true)
        ->assertJsonPath('data.impersonation.reason', 'Support ticket #42 — login issue investigation');

    $this->postJson('/api/platform/v1/impersonation/end')->assertOk();

    $this->assertDatabaseHas('impersonation_logs', [
        'organization_id' => $org['organization_id'],
        'impersonated_user_id' => $userId,
        'platform_admin_id' => $admin->id,
    ]);
});

test('platform admin crud requires platform guard', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'admin-crud-block@acme.test']);

    $this->getJson('/api/platform/v1/platform-admins', $this->organizationHeaders($org['token'], $org['organization_id']))
        ->assertUnauthorized();
});

test('platform admin can create another platform admin', function () {
    PlatformAdmin::factory()->create(['email' => 'bootstrap@acme.test', 'password' => 'password123']);
    Passport::actingAs(
        PlatformAdmin::query()->where('email', 'bootstrap@acme.test')->first(),
        [],
        'platform',
    );

    $this->postJson('/api/platform/v1/platform-admins', [
        'name' => 'Second Admin',
        'email' => 'second-admin@acme.test',
        'password' => 'password12345',
    ])->assertCreated()
        ->assertJsonPath('data.email', 'second-admin@acme.test');
});

test('cancelled subscription blocks tenant api access', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'cancelled-sub@acme.test',
    ]))->assertCreated();

    $organizationId = (int) $register->json('data.organizations.0.id');
    $token = $register->json('data.token.access_token');
    $headers = $this->organizationHeaders($token, $organizationId);

    PlatformAdmin::factory()->create(['email' => 'cancel-admin@acme.test', 'password' => 'password123']);
    Passport::actingAs(
        PlatformAdmin::query()->where('email', 'cancel-admin@acme.test')->first(),
        [],
        'platform',
    );

    $this->patchJson("/api/platform/v1/organizations/{$organizationId}/subscription", [
        'plan_id' => Plan::query()->where('slug', 'growth')->value('id'),
        'status' => 'cancelled',
    ])->assertOk();

    $this->getJson('/api/v1/products', $headers)
        ->assertForbidden()
        ->assertJsonPath('message', 'This organization subscription has been cancelled.');
});

test('expired trial allows reads and marks subscription expired', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'expired-trial@acme.test',
    ]))->assertCreated();

    $organizationId = (int) $register->json('data.organizations.0.id');
    $token = $register->json('data.token.access_token');
    $headers = $this->organizationHeaders($token, $organizationId);

    $organization = Organization::query()->findOrFail($organizationId);
    $subscription = $organization->subscription;
    $subscription->forceFill([
        'trial_ends_at' => now()->subDay(),
        'current_period_ends_at' => now()->subDay(),
    ])->save();

    $this->getJson('/api/v1/products', $headers)->assertOk();

    expect($subscription->fresh()->status)->toBe(SubscriptionStatus::Expired);
});

test('starter plan allows one warehouse then blocks the second', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'starter-plan@acme.test',
    ]))->assertCreated();

    $organizationId = (int) $register->json('data.organizations.0.id');
    $token = $register->json('data.token.access_token');
    $headers = $this->organizationHeaders($token, $organizationId);

    PlatformAdmin::factory()->create(['email' => 'starter-admin@acme.test', 'password' => 'password123']);
    Passport::actingAs(
        PlatformAdmin::query()->where('email', 'starter-admin@acme.test')->first(),
        [],
        'platform',
    );

    $starterPlan = Plan::query()->where('slug', 'starter')->firstOrFail();

    $this->patchJson("/api/platform/v1/organizations/{$organizationId}/subscription", [
        'plan_id' => $starterPlan->id,
        'status' => 'active',
    ])->assertOk();

    $this->postJson('/api/v1/warehouses', ['name' => 'WH 1', 'address' => 'A'], $headers)->assertCreated();
    $this->postJson('/api/v1/warehouses', ['name' => 'WH 2', 'address' => 'B'], $headers)->assertCreated();

    $this->postJson('/api/v1/warehouses', ['name' => 'WH 3', 'address' => 'C'], $headers)
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Upgrade required'));
});
