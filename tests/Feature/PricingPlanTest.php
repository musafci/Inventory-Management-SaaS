<?php

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Plan;
use App\Models\Product;
use App\Models\Customer;
use App\Models\SalesOrder;
use App\Models\Warehouse;
use App\Services\OrganizationSubscriptionService;
use App\Services\PlanLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('plan seeder matches pricing plan specification', function () {
    $starter = Plan::query()->where('slug', 'starter')->firstOrFail();
    $growth = Plan::query()->where('slug', 'growth')->firstOrFail();
    $business = Plan::query()->where('slug', 'business')->firstOrFail();
    $enterprise = Plan::query()->where('slug', 'enterprise')->firstOrFail();

    expect($starter->price_monthly)->toBe('29.00')
        ->and($starter->price_annual)->toBe('288.00')
        ->and($starter->limits['max_warehouses'])->toBe(1)
        ->and($starter->limits['max_products'])->toBe(200)
        ->and($starter->limits['api_rate_limit_per_minute'])->toBeNull()
        ->and($growth->price_monthly)->toBe('79.00')
        ->and($growth->price_annual)->toBe('780.00')
        ->and($growth->limits['max_users'])->toBe(10)
        ->and($business->price_monthly)->toBe('199.00')
        ->and($business->limits['max_products'])->toBeNull()
        ->and($enterprise->is_custom)->toBeTrue()
        ->and($enterprise->price_monthly)->toBeNull();

    expect(Plan::query()->where('slug', 'trial')->exists())->toBeFalse();
});

test('registration assigns a 14 day growth trial subscription', function () {
    $response = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'growth-trial@acme.test',
    ]))->assertCreated();

    $organizationId = (int) $response->json('data.organizations.0.id');
    $organization = Organization::query()->findOrFail($organizationId);

    expect($organization->plan)->toBe('growth')
        ->and($organization->trial_ends_at)->not->toBeNull()
        ->and($organization->trial_ends_at->greaterThan(now()->addDays(13)))->toBeTrue();

    $subscription = $organization->subscription;
    expect($subscription->status)->toBe(SubscriptionStatus::Trial)
        ->and($subscription->plan->slug)->toBe('growth');
});

test('expire trials command marks only past due trials as expired', function () {
    $converted = $this->registerOrganizationWithOwner(['email' => 'converted@acme.test']);
    $convertedOrg = Organization::query()->findOrFail($converted['organization_id']);
    app(OrganizationSubscriptionService::class)->updateSubscription(
        $convertedOrg,
        Plan::query()->where('slug', 'starter')->firstOrFail(),
        SubscriptionStatus::Active,
    );

    $trial = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'expire-me@acme.test',
    ]))->assertCreated();

    $trialOrg = Organization::query()->findOrFail((int) $trial->json('data.organizations.0.id'));
    $trialOrg->subscription->forceFill(['trial_ends_at' => now()->subDay()])->save();

    $this->artisan('subscriptions:expire-trials')->assertSuccessful();

    expect($trialOrg->subscription->fresh()->status)->toBe(SubscriptionStatus::Expired)
        ->and($convertedOrg->subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
});

test('org at 95 percent of order limit receives warning meta but can still create orders', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'order-warning@acme.test']);
    $organization = Organization::query()->findOrFail($org['organization_id']);

    app(OrganizationSubscriptionService::class)->updateSubscription(
        $organization,
        Plan::query()->where('slug', 'growth')->firstOrFail(),
        SubscriptionStatus::Active,
    );

    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $customer = Customer::factory()->create(['organization_id' => $organization->id]);
    $warehouse = Warehouse::factory()->create(['organization_id' => $organization->id]);

    SalesOrder::factory()->count(1900)->create([
        'organization_id' => $organization->id,
        'customer_id' => $customer->id,
        'warehouse_id' => $warehouse->id,
        'created_at' => now(),
    ]);

    $this->getJson('/api/v1/products', $headers)
        ->assertOk()
        ->assertJsonPath('meta.plan_warning', 'approaching_limit');
});

test('org past grace buffer gets upgrade required on product create', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'grace-block@acme.test']);
    $organization = Organization::query()->findOrFail($org['organization_id']);

    app(OrganizationSubscriptionService::class)->updateSubscription(
        $organization,
        Plan::query()->where('slug', 'starter')->firstOrFail(),
        SubscriptionStatus::Active,
    );

    Product::factory()->count(220)->create(['organization_id' => $organization->id]);

    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $category = \App\Models\Category::factory()->create(['organization_id' => $organization->id]);
    $unit = \App\Models\Unit::factory()->create(['organization_id' => $organization->id]);

    $this->postJson('/api/v1/products', [
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Blocked Product',
        'sku' => 'BLOCK-001',
        'cost_price' => 10,
        'selling_price' => 20,
    ], $headers)
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Upgrade required'));
});

test('expired trial allows product reads but blocks writes with 402', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'expired-readwrite@acme.test',
    ]))->assertCreated();

    $organizationId = (int) $register->json('data.organizations.0.id');
    $token = $register->json('data.token.access_token');
    $headers = $this->organizationHeaders($token, $organizationId);

    $organization = Organization::query()->findOrFail($organizationId);
    $organization->subscription->forceFill([
        'status' => SubscriptionStatus::Expired,
        'trial_ends_at' => now()->subDay(),
    ])->save();

    $this->getJson('/api/v1/products', $headers)->assertOk();

    $this->postJson('/api/v1/products', [
        'category_id' => \App\Models\Category::factory()->create(['organization_id' => $organizationId])->id,
        'unit_id' => \App\Models\Unit::factory()->create(['organization_id' => $organizationId])->id,
        'name' => 'Should Fail',
        'sku' => 'FAIL-001',
        'cost_price' => 10,
        'selling_price' => 20,
    ], $headers)
        ->assertStatus(402)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Choose a plan'));
});

test('plan limit service graduated check logic matches thresholds', function () {
    $organization = Organization::factory()->create();
    $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

    app(OrganizationSubscriptionService::class)->updateSubscription(
        $organization,
        $plan,
        SubscriptionStatus::Active,
    );

    $service = app(PlanLimitService::class);

    Product::factory()->count(180)->create(['organization_id' => $organization->id]);
    expect($service->evaluateOrganizationWarnings($organization->fresh()))->toBe('approaching_limit');

    Product::factory()->count(20)->create(['organization_id' => $organization->id]);
    expect($service->evaluateOrganizationWarnings($organization->fresh()))->toBe('over_limit_grace');
});
