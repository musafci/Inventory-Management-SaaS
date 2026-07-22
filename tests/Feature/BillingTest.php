<?php

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Plan;
use App\Services\OrganizationSubscriptionService;
use App\Services\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Stripe\Subscription as StripeSubscription;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('billing overview returns self serve plans', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'billing-overview@acme.test']);

    $this->getJson('/api/v1/billing', $this->organizationHeaders($org['token'], $org['organization_id']))
        ->assertOk()
        ->assertJsonPath('data.available_plans.0.slug', 'starter')
        ->assertJsonPath('data.available_plans.1.slug', 'growth')
        ->assertJsonPath('data.stripe_configured', false);
});

test('expired trial can access billing and read products but not writes', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'billing-access@acme.test',
    ]))->assertCreated();

    $organizationId = (int) $register->json('data.organizations.0.id');
    $token = $register->json('data.token.access_token');
    $organization = Organization::query()->findOrFail($organizationId);

    $organization->subscription->forceFill([
        'status' => SubscriptionStatus::Expired,
        'trial_ends_at' => now()->subDay(),
    ])->save();

    $headers = $this->organizationHeaders($token, $organizationId);

    $this->getJson('/api/v1/billing', $headers)->assertOk();
    $this->getJson('/api/v1/products', $headers)->assertOk();
});

test('stripe activation upgrades organization to selected plan', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'stripe-activate@acme.test']);
    $organization = Organization::query()->findOrFail($org['organization_id']);
    $starter = Plan::query()->where('slug', 'starter')->firstOrFail();

    $stripeSubscription = StripeSubscription::constructFrom([
        'id' => 'sub_test_123',
        'status' => 'active',
        'current_period_end' => now()->addMonth()->timestamp,
        'metadata' => [
            'organization_id' => (string) $organization->id,
            'plan_slug' => 'starter',
            'billing_interval' => 'monthly',
        ],
        'items' => [
            'data' => [
                [
                    'price' => [
                        'recurring' => ['interval' => 'month'],
                    ],
                ],
            ],
        ],
    ]);

    app(StripeBillingService::class)->activateFromStripeSubscription($organization, $stripeSubscription, $starter);

    $organization->refresh();
    $subscription = $organization->subscription()->with('plan')->first();

    expect($organization->plan)->toBe('starter')
        ->and($subscription->status)->toBe(SubscriptionStatus::Active)
        ->and($subscription->plan->slug)->toBe('starter')
        ->and($subscription->billing_interval)->toBe('monthly');
});

test('checkout requires configured stripe keys', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'checkout-fail@acme.test']);

    $this->postJson('/api/v1/billing/checkout', [
        'plan_slug' => 'starter',
        'interval' => 'monthly',
    ], $this->organizationHeaders($org['token'], $org['organization_id']))
        ->assertStatus(422)
        ->assertJsonPath('message', fn (string $message): bool => str_contains($message, 'Stripe is not configured'));
});
