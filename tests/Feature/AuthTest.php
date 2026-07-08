<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

function validRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'organization_name' => 'Acme Inventory',
        'name' => 'Jane Owner',
        'email' => 'jane@acme.test',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'phone' => '+15551234567',
    ], $overrides);
}

test('user can register an organization and receive a passport token', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegistrationPayload());

    $response->assertCreated()
        ->assertJsonPath('data.user.email', 'jane@acme.test')
        ->assertJsonPath('data.organizations.0.name', 'Acme Inventory')
        ->assertJsonPath('data.organizations.0.role', 'Org Owner')
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'name', 'email'],
                'organizations' => [['id', 'name', 'slug', 'role']],
                'token' => ['access_token', 'refresh_token', 'expires_in', 'token_type'],
            ],
        ]);

    $this->assertDatabaseHas('organizations', ['name' => 'Acme Inventory']);
    $this->assertDatabaseHas('users', ['email' => 'jane@acme.test']);
    $this->assertDatabaseHas('organization_user', ['role' => 'Org Owner']);
});

test('registering twice with the same email fails validation', function () {
    $this->postJson('/api/v1/auth/register', validRegistrationPayload())->assertCreated();

    $response = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'organization_name' => 'Another Org',
        'email' => 'jane@acme.test',
    ]));

    $response->assertUnprocessable()
        ->assertJsonPath('message', 'The given data was invalid.')
        ->assertJsonValidationErrors(['email']);
});

test('user can login and receive token with organizations', function () {
    $this->postJson('/api/v1/auth/register', validRegistrationPayload())->assertCreated();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@acme.test',
        'password' => 'password123',
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.email', 'jane@acme.test')
        ->assertJsonCount(1, 'data.organizations')
        ->assertJsonStructure([
            'data' => [
                'token' => ['access_token', 'refresh_token', 'expires_in', 'token_type'],
            ],
        ]);
});

test('login with wrong password fails auth', function () {
    $this->postJson('/api/v1/auth/register', validRegistrationPayload())->assertCreated();

    $response = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@acme.test',
        'password' => 'wrong-password',
    ]);

    $response->assertUnauthorized()
        ->assertJsonStructure(['message', 'errors']);
});

test('user can refresh an access token', function () {
    $registerResponse = $this->postJson('/api/v1/auth/register', validRegistrationPayload())->assertCreated();

    $refreshToken = $registerResponse->json('data.token.refresh_token');

    $response = $this->postJson('/api/v1/auth/refresh', [
        'refresh_token' => $refreshToken,
    ]);

    $response->assertOk()
        ->assertJsonStructure([
            'data' => [
                'user' => ['id', 'email'],
                'organizations',
                'token' => ['access_token', 'refresh_token', 'expires_in', 'token_type'],
            ],
        ]);
});

test('authenticated user can fetch their profile and organizations', function () {
    $registerResponse = $this->postJson('/api/v1/auth/register', validRegistrationPayload())->assertCreated();

    $accessToken = $registerResponse->json('data.token.access_token');

    $response = $this->getJson('/api/v1/auth/me', [
        'Authorization' => 'Bearer '.$accessToken,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.email', 'jane@acme.test')
        ->assertJsonCount(1, 'data.organizations')
        ->assertJsonMissingPath('data.token');
});

test('auth me does not require organization header', function () {
    $registerResponse = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'me@acme.test',
    ]))->assertCreated();

    $response = $this->getJson('/api/v1/auth/me', [
        'Authorization' => 'Bearer '.$registerResponse->json('data.token.access_token'),
    ]);

    $response->assertOk()
        ->assertJsonPath('data.user.email', 'me@acme.test')
        ->assertJsonCount(1, 'data.organizations');
});
