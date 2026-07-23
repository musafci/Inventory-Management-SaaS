<?php

use App\Models\PlatformAdmin;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('platform admin can login to web portal and browse organizations', function () {
    PlatformAdmin::factory()->create([
        'email' => 'platform-web@acme.test',
        'password' => 'password123',
    ]);

    $this->post('/platform/login', [
        'email' => 'platform-web@acme.test',
        'password' => 'password123',
    ])->assertRedirect(route('platform.dashboard'));

    expect(session('platform_auth_token'))->not->toBeEmpty();

    $this->get('/platform/dashboard')->assertOk();
    $this->get('/platform/organizations')->assertOk();
});

test('guest is redirected from platform dashboard to login', function () {
    $this->get('/platform/dashboard')->assertRedirect('/platform/login');
});

test('platform admin can view organization detail page', function () {
    PlatformAdmin::factory()->create([
        'email' => 'platform-detail@acme.test',
        'password' => 'password123',
    ]);

    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'organization_name' => 'Portal Detail Org',
        'email' => 'portal-detail-owner@acme.test',
    ]))->assertCreated();

    $organizationId = $register->json('data.organizations.0.id');

    $this->post('/platform/login', [
        'email' => 'platform-detail@acme.test',
        'password' => 'password123',
    ])->assertRedirect(route('platform.dashboard'));

    $this->get("/platform/organizations/{$organizationId}")
        ->assertOk()
        ->assertSee('Portal Detail Org')
        ->assertSee('Tenant controls');
});

test('platform login page is accessible to guests', function () {
    $this->get('/platform/login')
        ->assertOk()
        ->assertSee('Platform Admin');
});

test('platform admin can login as tenant user from organization page', function () {
    PlatformAdmin::factory()->create([
        'email' => 'platform-impersonate@acme.test',
        'password' => 'password123',
    ]);

    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'organization_name' => 'Impersonate Target Org',
        'email' => 'impersonate-owner@acme.test',
    ]))->assertCreated();

    $organizationId = $register->json('data.organizations.0.id');
    $userId = $register->json('data.user.id');

    $this->post('/platform/login', [
        'email' => 'platform-impersonate@acme.test',
        'password' => 'password123',
    ])->assertRedirect(route('platform.dashboard'));

    $this->post("/platform/organizations/{$organizationId}/impersonate", [
        'user_id' => $userId,
        'reason' => 'Support ticket #99 — reproducing dashboard issue',
    ])->assertRedirect(route('dashboard'));

    expect(session('auth_token'))->not->toBeEmpty();
    expect(session('organization_id'))->toBe($organizationId);
    expect(session('impersonation.organization_id'))->toBe($organizationId);
    expect(session('platform_auth_token'))->toBeNull();

    $this->get('/dashboard')
        ->assertOk()
        ->assertSee('Support impersonation active')
        ->assertSee('Impersonate Target Org');

    $this->post(route('impersonation.exit'))
        ->assertRedirect(route('platform.organizations.show', $organizationId));

    expect(session('platform_auth_token'))->not->toBeEmpty();
    expect(session('auth_token'))->toBeNull();
    expect(session('impersonation'))->toBeNull();

    $this->assertDatabaseHas('impersonation_logs', [
        'organization_id' => $organizationId,
        'impersonated_user_id' => $userId,
    ]);

    expect(\App\Models\ImpersonationLog::query()->where('organization_id', $organizationId)->value('ended_at'))->not->toBeNull();
});
