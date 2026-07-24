<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('authenticated user can register an expo push token', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'push-register@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $response = $this->postJson('/api/v1/devices/push-token', [
        'expo_push_token' => 'ExponentPushToken[abc123]',
        'platform' => 'android',
        'device_name' => 'Pixel 8',
        'organization_id' => $org['organization_id'],
    ], $headers);

    $response->assertCreated()
        ->assertJsonPath('data.expo_push_token', 'ExponentPushToken[abc123]')
        ->assertJsonPath('data.platform', 'android')
        ->assertJsonPath('data.organization_id', $org['organization_id']);

    $this->assertDatabaseHas('device_push_tokens', [
        'expo_push_token' => 'ExponentPushToken[abc123]',
        'platform' => 'android',
        'device_name' => 'Pixel 8',
    ]);
});

test('registering the same expo push token updates the existing row', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'push-upsert@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/devices/push-token', [
        'expo_push_token' => 'ExponentPushToken[shared]',
        'platform' => 'ios',
        'device_name' => 'iPhone 15',
        'organization_id' => $org['organization_id'],
    ], $headers)->assertCreated();

    $this->postJson('/api/v1/devices/push-token', [
        'expo_push_token' => 'ExponentPushToken[shared]',
        'platform' => 'ios',
        'device_name' => 'iPhone 16',
        'organization_id' => $org['organization_id'],
    ], $headers)->assertCreated()
        ->assertJsonPath('data.device_name', 'iPhone 16');

    expect(\App\Models\DevicePushToken::query()->count())->toBe(1);
});

test('user cannot register push token for an organization they do not belong to', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'push-org-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'push-org-b@acme.test']);
    $headers = $this->organizationHeaders($orgA['token'], $orgA['organization_id']);

    $this->postJson('/api/v1/devices/push-token', [
        'expo_push_token' => 'ExponentPushToken[forbidden]',
        'platform' => 'android',
        'organization_id' => $orgB['organization_id'],
    ], $headers)->assertForbidden();
});

test('authenticated user can unregister their expo push token', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'push-delete@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/devices/push-token', [
        'expo_push_token' => 'ExponentPushToken[delete-me]',
        'platform' => 'android',
        'organization_id' => $org['organization_id'],
    ], $headers)->assertCreated();

    $this->deleteJson('/api/v1/devices/push-token', [
        'expo_push_token' => 'ExponentPushToken[delete-me]',
    ], $headers)->assertNoContent();

    $this->assertDatabaseMissing('device_push_tokens', [
        'expo_push_token' => 'ExponentPushToken[delete-me]',
    ]);
});

test('unregister requires authentication', function () {
    $this->deleteJson('/api/v1/devices/push-token', [
        'expo_push_token' => 'ExponentPushToken[anon]',
    ])->assertUnauthorized();
});
