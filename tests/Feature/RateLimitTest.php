<?php

use App\Models\Plan;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\RateLimiter;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setUpPassport();
    Cache::flush();
    RateLimiter::clear('api-tenant');
    config(['api.rate_limit_per_minute' => 3]);

    $trial = Plan::query()->where('slug', 'trial')->firstOrFail();
    $limits = $trial->limits;
    $limits['api_rate_limit'] = 3;
    $trial->forceFill(['limits' => $limits])->save();
});

test('tenant api rate limit is keyed per organization not ip', function () {
    $registerA = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'rate-a@acme.test',
    ]))->assertCreated();

    $registerB = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'rate-b@acme.test',
    ]))->assertCreated();

    $orgA = [
        'organization_id' => (int) $registerA->json('data.organizations.0.id'),
        'token' => $registerA->json('data.token.access_token'),
    ];

    $orgB = [
        'organization_id' => (int) $registerB->json('data.organizations.0.id'),
        'token' => $registerB->json('data.token.access_token'),
    ];

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
