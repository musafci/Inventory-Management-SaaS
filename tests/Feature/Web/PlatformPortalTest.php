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
