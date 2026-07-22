<?php

use App\Services\Web\WebSessionService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('web session normalizes organization role from pivot data', function () {
    $service = app(WebSessionService::class);

    $normalized = $service->normalizeOrganizations([[
        'id' => 1,
        'name' => 'Acme Warehouse',
        'slug' => 'acme-warehouse',
        'email' => 'owner@acme.demo',
        'phone' => null,
        'plan' => 'trial',
        'status' => 'trial',
        'trial_ends_at' => null,
        'pivot' => [
            'role' => 'Org Owner',
        ],
    ]]);

    expect($normalized[0]['role'])->toBe('Org Owner')
        ->and($normalized[0])->not->toHaveKey('pivot');
});

test('organization session reads role from pivot when top level role is missing', function () {
    session([
        'organization_id' => 1,
        'organizations' => [[
            'id' => 1,
            'name' => 'Acme Warehouse',
            'pivot' => ['role' => 'Org Owner'],
        ]],
        'permissions' => [
            'settings.manage_users',
            'settings.update',
        ],
    ]);

    expect(\App\Support\OrganizationSession::currentRole())->toBe('Org Owner')
        ->and(\App\Support\OrganizationSession::canManageUsers())->toBeTrue()
        ->and(\App\Support\OrganizationSession::canManageOrganization())->toBeTrue();
});

test('system owner role bypasses permission checks in session', function () {
    session([
        'organization_id' => 1,
        'organizations' => [[
            'id' => 1,
            'name' => 'Acme Warehouse',
            'role' => 'System Owner',
        ]],
        'permissions' => [],
    ]);

    expect(\App\Support\OrganizationSession::can('inventory.delete'))->toBeTrue()
        ->and(\App\Support\OrganizationSession::canManageRoles())->toBeTrue();
});
