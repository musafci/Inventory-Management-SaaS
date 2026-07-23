<?php

use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use App\Services\ImpersonationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Client;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('passport ensure personal access clients command is idempotent', function () {
    expect(
        Client::query()->where('provider', 'users')->where('revoked', false)->get()
            ->filter(fn (Client $client): bool => $client->hasGrantType('personal_access'))
            ->count()
    )->toBe(1);

    $this->artisan('passport:ensure-personal-access-clients')->assertSuccessful();

    expect(
        Client::query()->where('provider', 'users')->where('revoked', false)->get()
            ->filter(fn (Client $client): bool => $client->hasGrantType('personal_access'))
            ->count()
    )->toBe(1);
    expect(
        Client::query()->where('provider', 'platform_admins')->where('revoked', false)->get()
            ->filter(fn (Client $client): bool => $client->hasGrantType('personal_access'))
            ->count()
    )->toBe(1);
});

test('passport ensure personal access clients creates missing user client', function () {
    Client::query()
        ->where('provider', 'users')
        ->get()
        ->filter(fn (Client $client): bool => $client->hasGrantType('personal_access'))
        ->each(fn (Client $client) => $client->delete());

    expect(
        Client::query()->where('provider', 'users')->where('revoked', false)->get()
            ->contains(fn (Client $client): bool => $client->hasGrantType('personal_access'))
    )->toBeFalse();

    $this->artisan('passport:ensure-personal-access-clients')->assertSuccessful();

    expect(
        Client::query()->where('provider', 'users')->where('revoked', false)->get()
            ->contains(fn (Client $client): bool => $client->hasGrantType('personal_access'))
    )->toBeTrue();
});

test('impersonation auto provisions user personal access client when missing', function () {
    Client::query()
        ->where('provider', 'users')
        ->get()
        ->filter(fn (Client $client): bool => $client->hasGrantType('personal_access'))
        ->each(fn (Client $client) => $client->delete());

    $admin = PlatformAdmin::factory()->create();
    $org = $this->registerOrganizationWithOwner(['email' => 'auto-provision@acme.test']);
    $user = User::query()->findOrFail($org['response']->json('data.user.id'));
    $organization = Organization::query()->findOrFail($org['organization_id']);

    app(ImpersonationService::class)->start(
        $admin,
        $organization,
        $user,
        'Testing auto-provision',
    );

    expect(
        Client::query()->where('provider', 'users')->where('revoked', false)->get()
            ->contains(fn (Client $client): bool => $client->hasGrantType('personal_access'))
    )->toBeTrue();
});
