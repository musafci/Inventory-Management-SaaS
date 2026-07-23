<?php

use App\Mail\OrganizationRegisteredMail;
use App\Models\Organization;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

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

test('organization registration sends notification email to platform admin', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'notify-owner@acme.test',
        'organization_name' => 'Notify Corp',
    ]))->assertCreated();

    Mail::assertSent(OrganizationRegisteredMail::class, function (OrganizationRegisteredMail $mail): bool {
        return $mail->hasTo('oneapp.com.bd@gmail.com')
            && $mail->organization->name === 'Notify Corp'
            && $mail->owner->email === 'notify-owner@acme.test';
    });
});

test('organization registration does not send email when registration fails', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'jane@acme.test',
    ]))->assertCreated();

    Mail::fake();

    $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'organization_name' => 'Duplicate Org',
        'email' => 'jane@acme.test',
    ]))->assertUnprocessable();

    Mail::assertNothingSent();
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
        ->assertJsonCount(1, 'data.organizations')
        ->assertJsonPath('data.active_organization_id', null)
        ->assertJsonPath('data.permissions', []);
});

test('auth me returns permissions when organization header is provided', function () {
    $registerResponse = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'me-perms@acme.test',
    ]))->assertCreated();

    $accessToken = $registerResponse->json('data.token.access_token');
    $organizationId = $registerResponse->json('data.organizations.0.id');

    $response = $this->getJson('/api/v1/auth/me', [
        'Authorization' => 'Bearer '.$accessToken,
        'X-Organization-Id' => (string) $organizationId,
    ]);

    $response->assertOk()
        ->assertJsonPath('data.active_organization_id', $organizationId);

    expect($response->json('data.permissions'))->toContain('inventory.view')
        ->and($response->json('data.permissions'))->toContain('orders.sales.create')
        ->and($response->json('data.permissions'))->toContain('settings.view');
});

test('auth me rejects organization header for org user does not belong to', function () {
    $owner = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'me-owner@acme.test',
        'organization_name' => 'Owner Org',
    ]))->assertCreated();

    $other = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'me-other@acme.test',
        'organization_name' => 'Other Org',
    ]))->assertCreated();

    $this->getJson('/api/v1/auth/me', [
        'Authorization' => 'Bearer '.$owner->json('data.token.access_token'),
        'X-Organization-Id' => (string) $other->json('data.organizations.0.id'),
    ])->assertForbidden()
        ->assertJsonPath('message', 'You do not belong to this organization.');
});
