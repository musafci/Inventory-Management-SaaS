<?php

use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setUpPassport();
    Cache::flush();
    RateLimiter::clear('api-tenant');
    config(['api.rate_limit_per_minute' => 3]);
});

test('tenant api rate limit is keyed per organization not ip', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'rate-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'rate-b@acme.test']);

    foreach (range(1, 3) as $attempt) {
        $this->getJson(
            '/api/v1/warehouses',
            $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
        )->assertOk();
    }

    $this->getJson(
        '/api/v1/warehouses',
        $this->organizationContextHeaders($orgA['token'], $orgA['organization_id']),
    )
        ->assertStatus(429)
        ->assertJsonPath('message', 'Too many requests.')
        ->assertHeader('Retry-After');

    $this->getJson(
        '/api/v1/warehouses',
        $this->organizationContextHeaders($orgB['token'], $orgB['organization_id']),
    )->assertOk();
});

test('auth routes are not subject to tenant rate limiting', function () {
    config(['api.rate_limit_per_minute' => 1]);

    foreach (range(1, 3) as $attempt) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'nobody@acme.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }
});
