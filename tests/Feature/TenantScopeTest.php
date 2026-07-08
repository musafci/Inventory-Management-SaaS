<?php

use App\Http\Middleware\ResolveTenant;
use App\Models\Organization;
use App\Models\TenantScopeStub;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;

uses(RefreshDatabase::class);

test('tenant scoped queries fail closed when no organization context is bound', function () {
    $organizationA = Organization::factory()->create(['name' => 'Org A']);
    $organizationB = Organization::factory()->create(['name' => 'Org B']);

    TenantScopeStub::withoutOrganizationScope()->create([
        'organization_id' => $organizationA->id,
        'label' => 'Org A record',
    ]);

    TenantScopeStub::withoutOrganizationScope()->create([
        'organization_id' => $organizationB->id,
        'label' => 'Org B record',
    ]);

    expect(app()->bound('currentOrganization'))->toBeFalse();
    expect(TenantScopeStub::query()->count())->toBe(0);
    expect(TenantScopeStub::query()->get())->toHaveCount(0);
});

test('resolve tenant middleware rejects requests without organization header', function () {
    $user = User::factory()->create();

    $request = Request::create('/api/v1/tenant-scope-probe', 'GET');
    $request->setUserResolver(fn () => $user);

    $response = (new ResolveTenant)->handle(
        $request,
        fn () => response()->json(['ok' => true]),
    );

    expect($response->getStatusCode())->toBe(403);
});

test('resolve tenant middleware rejects users who do not belong to the organization', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $request = Request::create('/api/v1/tenant-scope-probe', 'GET', server: [
        'HTTP_X_ORGANIZATION_ID' => (string) $organization->id,
    ]);
    $request->setUserResolver(fn () => $user);

    $response = (new ResolveTenant)->handle(
        $request,
        fn () => response()->json(['ok' => true]),
    );

    expect($response->getStatusCode())->toBe(403);
});

test('resolve tenant middleware binds organization for members', function () {
    $organization = Organization::factory()->create();
    $user = User::factory()->create();

    $user->organizations()->attach($organization->id, ['role' => 'Owner']);

    $request = Request::create('/api/v1/tenant-scope-probe', 'GET', server: [
        'HTTP_X_ORGANIZATION_ID' => (string) $organization->id,
    ]);
    $request->setUserResolver(fn () => $user);

    $response = (new ResolveTenant)->handle(
        $request,
        fn () => response()->json([
            'organization_id' => app('currentOrganization')->id,
        ]),
    );

    expect($response->getStatusCode())->toBe(200)
        ->and($response->getData(true)['organization_id'])->toBe($organization->id);
});
