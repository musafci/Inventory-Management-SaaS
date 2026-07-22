<?php

use App\Enums\SubscriptionStatus;
use App\Mail\PasswordResetMail;
use App\Mail\PaymentFailedMail;
use App\Mail\TrialEndingSoonMail;
use App\Mail\WelcomeMail;
use App\Models\Organization;
use App\Models\OrganizationDataExport;
use App\Models\Plan;
use App\Models\Product;
use App\Models\StripeEvent;
use App\Models\User;
use App\Services\OrganizationSubscriptionService;
use App\Services\StripeBillingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Token;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->setUpPassport();
});

test('forgot password returns generic success for unknown email', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/forgot-password', ['email' => 'missing@acme.test'])
        ->assertOk()
        ->assertJsonPath('data.message', fn (string $message): bool => str_contains($message, 'If an account exists'));

    Mail::assertNothingSent();
});

test('password reset flow revokes existing tokens', function () {
    Mail::fake();

    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'reset-flow@acme.test',
    ]))->assertCreated();

    $oldToken = $register->json('data.token.access_token');
    $organizationId = (int) $register->json('data.organizations.0.id');
    $user = User::query()->where('email', 'reset-flow@acme.test')->firstOrFail();
    $resetToken = Password::broker()->createToken($user);

    $this->postJson('/api/v1/auth/reset-password', [
        'email' => 'reset-flow@acme.test',
        'token' => $resetToken,
        'password' => 'new-password-123',
        'password_confirmation' => 'new-password-123',
    ])->assertOk();

    app('auth')->forgetGuards();

    $this->getJson('/api/v1/products', $this->organizationHeaders($oldToken, $organizationId))
        ->assertUnauthorized();
});

test('login is rate limited per ip and email', function () {
    RateLimiter::clear('auth-login');

    for ($attempt = 0; $attempt < 5; $attempt++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'rate-limit@acme.test',
            'password' => 'wrong-password',
        ])->assertUnauthorized();
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'rate-limit@acme.test',
        'password' => 'wrong-password',
    ])->assertStatus(429);
});

test('registration sends welcome email', function () {
    Mail::fake();

    $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'welcome@acme.test',
    ]))->assertCreated();

    Mail::assertQueued(WelcomeMail::class, fn (WelcomeMail $mail): bool => $mail->owner->email === 'welcome@acme.test');
});

test('trial ending command sends reminder email once', function () {
    Mail::fake();

    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'trial-reminder@acme.test',
    ]))->assertCreated();

    $organization = Organization::query()->findOrFail((int) $register->json('data.organizations.0.id'));
    $reminderDays = (int) config('subscription.trial_ending_reminder_days', 3);

    $organization->subscription->forceFill([
        'trial_ends_at' => now()->addDays($reminderDays)->startOfDay()->addHours(12),
    ])->save();

    Artisan::call('subscriptions:notify-trial-ending');

    Mail::assertQueued(TrialEndingSoonMail::class);
    expect($organization->subscription->fresh()->trial_reminder_sent_at)->not->toBeNull();

    Mail::fake();
    Artisan::call('subscriptions:notify-trial-ending');
    Mail::assertNothingSent();
});

test('stripe webhook rejects invalid signature', function () {
    config(['stripe.webhook_secret' => 'whsec_test_secret']);

    $payload = json_encode(['id' => 'evt_invalid', 'type' => 'invoice.paid']);

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'HTTP_Stripe-Signature' => 'invalid',
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload,
    )->assertStatus(400);

    expect(StripeEvent::query()->count())->toBe(0);
});

test('stripe webhook skips duplicate event ids', function () {
    config(['stripe.webhook_secret' => 'whsec_test_secret_for_duplicate']);

    StripeEvent::query()->create([
        'event_id' => 'evt_duplicate_test',
        'type' => 'invoice.payment_failed',
    ]);

    $org = $this->registerOrganizationWithOwner(['email' => 'webhook-dup@acme.test']);
    $organization = Organization::query()->findOrFail($org['organization_id']);
    app(OrganizationSubscriptionService::class)->updateSubscription(
        $organization,
        Plan::query()->where('slug', 'starter')->firstOrFail(),
        SubscriptionStatus::Active,
    );

    $payload = json_encode([
        'id' => 'evt_duplicate_test',
        'type' => 'invoice.payment_failed',
        'data' => [
            'object' => [
                'subscription' => 'sub_dup_test',
            ],
        ],
    ]);

    $timestamp = time();
    $signedPayload = "{$timestamp}.{$payload}";
    $signature = hash_hmac('sha256', $signedPayload, 'whsec_test_secret_for_duplicate');
    $header = "t={$timestamp},v1={$signature}";

    $this->call(
        'POST',
        '/api/stripe/webhook',
        [],
        [],
        [],
        [
            'HTTP_Stripe-Signature' => $header,
            'CONTENT_TYPE' => 'application/json',
        ],
        $payload,
    )->assertOk();

    expect($organization->subscription->fresh()->status)->toBe(SubscriptionStatus::Active);
    expect(StripeEvent::query()->where('event_id', 'evt_duplicate_test')->count())->toBe(1);
});

test('past due organization can write during grace period but not after', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'past-due@acme.test']);
    $organization = Organization::query()->findOrFail($org['organization_id']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $category = \App\Models\Category::factory()->create(['organization_id' => $organization->id]);
    $unit = \App\Models\Unit::factory()->create(['organization_id' => $organization->id]);

    $organization->subscription->forceFill([
        'status' => SubscriptionStatus::PastDue,
        'past_due_at' => now()->subDays(2),
    ])->save();

    $this->postJson('/api/v1/products', [
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Grace Product',
        'sku' => 'GRACE-001',
        'cost_price' => 10,
        'selling_price' => 20,
    ], $headers)->assertCreated();

    $organization->subscription->forceFill([
        'past_due_at' => now()->subDays((int) config('subscription.past_due_grace_days', 7) + 1),
    ])->save();

    $this->postJson('/api/v1/products', [
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Blocked Product',
        'sku' => 'BLOCK-001',
        'cost_price' => 10,
        'selling_price' => 20,
    ], $headers)->assertPaymentRequired();
});

test('cancelled subscription blocks writes with payment required', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'cancelled@acme.test']);
    $organization = Organization::query()->findOrFail($org['organization_id']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $category = \App\Models\Category::factory()->create(['organization_id' => $organization->id]);
    $unit = \App\Models\Unit::factory()->create(['organization_id' => $organization->id]);

    $organization->subscription->forceFill([
        'status' => SubscriptionStatus::Cancelled,
    ])->save();

    $this->getJson('/api/v1/products', $headers)->assertOk();

    $this->postJson('/api/v1/products', [
        'category_id' => $category->id,
        'unit_id' => $unit->id,
        'name' => 'Should Fail',
        'sku' => 'FAIL-001',
        'cost_price' => 10,
        'selling_price' => 20,
    ], $headers)->assertPaymentRequired();
});

test('payment failed marks past due and sends dunning email', function () {
    Mail::fake();

    $org = $this->registerOrganizationWithOwner(['email' => 'dunning@acme.test']);
    $organization = Organization::query()->findOrFail($org['organization_id']);

    app(StripeBillingService::class)->markPastDue($organization);

    expect($organization->subscription->fresh()->status)->toBe(SubscriptionStatus::PastDue)
        ->and($organization->subscription->fresh()->past_due_at)->not->toBeNull();

    Mail::assertQueued(PaymentFailedMail::class);
});

test('organization data export is scoped to one tenant', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'export-a@acme.test']);
    $orgB = $this->registerOrganizationWithOwner(['email' => 'export-b@acme.test']);

    Product::factory()->create(['organization_id' => $orgA['organization_id'], 'name' => 'Product A']);
    Product::factory()->create(['organization_id' => $orgB['organization_id'], 'name' => 'Product B']);

    $this->postJson('/api/v1/organization/export', [], $this->organizationHeaders($orgA['token'], $orgA['organization_id']))
        ->assertAccepted();

    $export = OrganizationDataExport::query()
        ->withoutOrganizationScope()
        ->where('organization_id', $orgA['organization_id'])
        ->firstOrFail();

    expect($export->status)->toBe('completed');

    $contents = json_decode(
        Storage::disk('local')->get($export->file_path),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    $productNames = collect($contents['products'])->pluck('name');

    expect($productNames)->toContain('Product A')
        ->and($productNames)->not->toContain('Product B');
});

test('deletion request can be cancelled before grace period ends', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'delete-me@acme.test']);
    $headers = $this->organizationHeaders($org['token'], $org['organization_id']);

    $this->postJson('/api/v1/organization/request-deletion', [], $headers)
        ->assertOk()
        ->assertJsonPath('data.deletion_requested_at', fn ($value) => $value !== null);

    $this->postJson('/api/v1/organization/cancel-deletion', [], $headers)
        ->assertOk()
        ->assertJsonPath('data.deletion_requested_at', null);
});

test('scheduled deletion command removes organization past grace period', function () {
    $org = $this->registerOrganizationWithOwner(['email' => 'hard-delete@acme.test']);
    $organization = Organization::query()->findOrFail($org['organization_id']);

    $organization->forceFill([
        'deletion_requested_at' => now()->subDays(31),
        'deletion_scheduled_for' => now()->subDay(),
    ])->save();

    Artisan::call('organizations:process-deletions');

    expect(Organization::query()->find($organization->id))->toBeNull();
});

test('logout revokes only current token', function () {
    $register = $this->postJson('/api/v1/auth/register', validRegistrationPayload([
        'email' => 'sessions@acme.test',
    ]))->assertCreated();

    $tokenA = $register->json('data.token.access_token');
    $organizationId = (int) $register->json('data.organizations.0.id');

    $tokenB = $this->postJson('/api/v1/auth/login', [
        'email' => 'sessions@acme.test',
        'password' => 'password123',
    ])->json('data.token.access_token');

    $headersA = ['Authorization' => 'Bearer '.$tokenA];
    $headersB = ['Authorization' => 'Bearer '.$tokenB];
    $tenantHeadersB = $this->organizationHeaders($tokenB, $organizationId);

    $this->postJson('/api/v1/auth/logout', [], $headersA)->assertNoContent();

    app('auth')->forgetGuards();

    $this->getJson('/api/v1/auth/me', $headersA)->assertUnauthorized();
    $this->getJson('/api/v1/auth/me', $headersB)->assertOk();
    $this->getJson('/api/v1/products', $tenantHeadersB)->assertOk();
});

test('user cannot revoke another users session token', function () {
    $orgA = $this->registerOrganizationWithOwner(['email' => 'session-a@acme.test']);
    $this->registerOrganizationWithOwner(['email' => 'session-b@acme.test']);

    $tokenId = Token::query()
        ->where('user_id', User::query()->where('email', 'session-b@acme.test')->value('id'))
        ->value('id');

    $this->deleteJson('/api/v1/auth/sessions/'.$tokenId, [], [
        'Authorization' => 'Bearer '.$orgA['token'],
    ])->assertNotFound();
});

test('health endpoint reports service status', function () {
    $this->getJson('/api/health')
        ->assertOk()
        ->assertJsonPath('status', 'ok')
        ->assertJsonStructure(['checks' => ['database', 'redis', 'queue']]);
});
