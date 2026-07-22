<?php

use App\Models\Activity;
use App\Models\PlatformAdmin;
use App\Models\SalesOrder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->setUpPassport();
});

test('platform admin can list organization activity logs with summary', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'platform-audit@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);
    $catalog = createSalesCatalog($this, $headers);

    $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalog['customer_id'],
        'warehouse_id' => $catalog['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalog['product_id'], 'quantity' => 1, 'unit_price' => 10],
        ],
    ], withIdempotencyKey($headers))->assertCreated();

    $admin = PlatformAdmin::factory()->create(['email' => 'audit-admin@acme.test', 'password' => 'password123']);
    Passport::actingAs($admin, [], 'platform');

    $this->getJson("/api/platform/v1/organizations/{$org['organization_id']}/activity-logs?subject_type=sales_order")
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.organization_id', $org['organization_id'])
        ->assertJsonPath('data.0.event', 'created')
        ->assertJsonPath('data.0.subject.type', 'SalesOrder')
        ->assertJsonPath('meta.summary.total', fn (int $total): bool => $total >= 1)
        ->assertJsonPath('meta.summary.last_24_hours', fn (int $count): bool => $count >= 1);
});

test('platform admin can filter cross tenant activity logs and summary', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'audit-a@acme.test']);
    $headersA = $this->organizationHeaders($orgA['token'], $orgA['organization_id']);
    $catalogA = createSalesCatalog($this, $headersA);

    $this->postJson('/api/v1/sales-orders', [
        'customer_id' => $catalogA['customer_id'],
        'warehouse_id' => $catalogA['warehouse_id'],
        'order_date' => '2026-07-09',
        'items' => [
            ['product_id' => $catalogA['product_id'], 'quantity' => 1, 'unit_price' => 10],
        ],
    ], withIdempotencyKey($headersA))->assertCreated();

    $orgB = $this->registerOrganizationWithOwner(['email' => 'audit-b@acme.test']);

    $admin = PlatformAdmin::factory()->create(['email' => 'audit-cross@acme.test', 'password' => 'password123']);
    Passport::actingAs($admin, [], 'platform');

    $this->getJson('/api/platform/v1/activity-logs?organization_id='.$orgA['organization_id'].'&subject_type=sales_order')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.organization_id', $orgA['organization_id']);

    $this->getJson('/api/platform/v1/activity-logs/summary?organization_id='.$orgA['organization_id'])
        ->assertOk()
        ->assertJsonPath('data.total', fn (int $total): bool => $total >= 1);

    expect(Activity::query()
        ->where('organization_id', $orgB['organization_id'])
        ->where('subject_type', SalesOrder::class)
        ->count())->toBe(0);
});

test('tenant token cannot access platform activity logs', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'audit-block@acme.test']);

    $this->getJson('/api/platform/v1/activity-logs', $this->organizationHeaders($org['token'], $org['organization_id']))
        ->assertUnauthorized();
});
