<?php

namespace App\Services;

use App\Enums\SubscriptionStatus;
use App\Models\Organization;
use App\Models\Plan;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use RuntimeException;
use Stripe\Exception\ApiErrorException;
use Stripe\StripeClient;
use Stripe\Subscription as StripeSubscription;
use Stripe\Webhook;

class StripeBillingService
{
    protected StripeClient $stripe;

    public function __construct()
    {
        $this->stripe = new StripeClient(config('stripe.secret'));
    }

    /**
     * @return array{subscription: ?\App\Models\OrganizationSubscription, available_plans: Collection<int, Plan>, stripe_configured: bool}
     */
    public function billingOverview(Organization $organization): array
    {
        $subscription = app(OrganizationSubscriptionService::class)
            ->activeSubscription($organization);

        return [
            'subscription' => $subscription?->load('plan'),
            'available_plans' => $this->selfServePlans(),
            'stripe_configured' => $this->isConfigured(),
        ];
    }

    /**
     * @return Collection<int, Plan>
     */
    public function selfServePlans(): Collection
    {
        return Plan::query()
            ->where('is_active', true)
            ->where('is_custom', false)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * @return array{url: string}
     */
    public function createCheckoutSession(Organization $organization, string $planSlug, string $interval): array
    {
        $this->ensureConfigured();

        $plan = Plan::query()
            ->where('slug', $planSlug)
            ->where('is_custom', false)
            ->where('is_active', true)
            ->firstOrFail();

        $interval = $this->normalizeInterval($interval);
        $priceId = $this->priceIdForPlan($plan->slug, $interval);
        $customerId = $this->ensureStripeCustomer($organization);

        $session = $this->stripe->checkout->sessions->create([
            'mode' => 'subscription',
            'customer' => $customerId,
            'line_items' => [
                ['price' => $priceId, 'quantity' => 1],
            ],
            'success_url' => url('/settings/billing?checkout=success'),
            'cancel_url' => url('/settings/billing?checkout=cancelled'),
            'client_reference_id' => (string) $organization->id,
            'metadata' => [
                'organization_id' => (string) $organization->id,
                'plan_slug' => $plan->slug,
                'billing_interval' => $interval,
            ],
            'subscription_data' => [
                'metadata' => [
                    'organization_id' => (string) $organization->id,
                    'plan_slug' => $plan->slug,
                    'billing_interval' => $interval,
                ],
            ],
        ]);

        if ($session->url === null) {
            throw new RuntimeException('Stripe did not return a checkout URL.');
        }

        return ['url' => $session->url];
    }

    /**
     * @return array{url: string}
     */
    public function createPortalSession(Organization $organization): array
    {
        $this->ensureConfigured();

        $customerId = $organization->stripe_customer_id;

        if ($customerId === null) {
            throw new RuntimeException('No Stripe customer exists for this organization.');
        }

        $session = $this->stripe->billingPortal->sessions->create([
            'customer' => $customerId,
            'return_url' => url('/settings/billing'),
        ]);

        return ['url' => $session->url];
    }

    public function handleWebhook(string $payload, ?string $signature): void
    {
        $secret = config('stripe.webhook_secret');

        if ($secret === null || $secret === '' || str_starts_with($secret, 'whsec_dummy')) {
            throw new RuntimeException('Stripe webhook secret is not configured.');
        }

        if ($signature === null || $signature === '') {
            throw new RuntimeException('Missing Stripe signature header.');
        }

        $event = Webhook::constructEvent($payload, $signature, $secret);

        match ($event->type) {
            'checkout.session.completed' => $this->handleCheckoutCompleted($event->data->object),
            'customer.subscription.updated' => $this->handleSubscriptionUpdated($event->data->object),
            'customer.subscription.deleted' => $this->handleSubscriptionDeleted($event->data->object),
            'invoice.payment_failed' => $this->handlePaymentFailed($event->data->object),
            default => null,
        };
    }

    public function activateFromStripeSubscription(
        Organization $organization,
        StripeSubscription $stripeSubscription,
        ?Plan $plan = null,
    ): void {
        $planSlug = $stripeSubscription->metadata['plan_slug'] ?? null;
        $plan ??= $planSlug
            ? Plan::query()->where('slug', $planSlug)->firstOrFail()
            : $this->selfServePlans()->firstOrFail();

        $interval = $stripeSubscription->metadata['billing_interval'] ?? $this->intervalFromStripe($stripeSubscription);
        $periodEnd = $stripeSubscription->current_period_end
            ? \Illuminate\Support\Carbon::createFromTimestamp($stripeSubscription->current_period_end)
            : null;

        DB::transaction(function () use ($organization, $plan, $stripeSubscription, $interval, $periodEnd): void {
            $subscriptionService = app(OrganizationSubscriptionService::class);

            $subscription = $subscriptionService->updateSubscription(
                $organization,
                $plan,
                SubscriptionStatus::Active,
                null,
                $periodEnd,
            );

            $subscription->forceFill([
                'stripe_subscription_id' => $stripeSubscription->id,
                'billing_interval' => $interval,
            ])->save();

            $organization->forceFill([
                'status' => \App\Enums\OrganizationStatus::Active,
            ])->save();

            $subscriptionService->syncOrganizationPlanCache($organization->fresh(), $subscription);
        });
    }

    public function markPastDue(Organization $organization): void
    {
        $subscription = app(OrganizationSubscriptionService::class)->activeSubscription($organization);

        if ($subscription === null) {
            return;
        }

        $subscription->forceFill(['status' => SubscriptionStatus::PastDue])->save();
    }

    public function markCancelled(Organization $organization): void
    {
        $subscription = app(OrganizationSubscriptionService::class)->activeSubscription($organization);

        if ($subscription === null) {
            return;
        }

        $subscription->forceFill([
            'status' => SubscriptionStatus::Cancelled,
            'stripe_subscription_id' => null,
            'billing_interval' => null,
        ])->save();
    }

    public function isConfigured(): bool
    {
        $secret = (string) config('stripe.secret');

        return $secret !== ''
            && ! str_starts_with($secret, 'sk_test_dummy');
    }

    protected function handleCheckoutCompleted(object $session): void
    {
        if ($session->mode !== 'subscription' || $session->subscription === null) {
            return;
        }

        $organization = $this->resolveOrganizationFromCheckout($session);

        if ($organization === null) {
            return;
        }

        $stripeSubscription = $this->stripe->subscriptions->retrieve($session->subscription);
        $planSlug = $session->metadata['plan_slug'] ?? $stripeSubscription->metadata['plan_slug'] ?? null;
        $plan = $planSlug ? Plan::query()->where('slug', $planSlug)->first() : null;

        $this->activateFromStripeSubscription($organization, $stripeSubscription, $plan);
    }

    protected function handleSubscriptionUpdated(object $stripeSubscription): void
    {
        $organization = $this->resolveOrganizationFromStripeSubscription($stripeSubscription);

        if ($organization === null) {
            return;
        }

        if (in_array($stripeSubscription->status, ['active', 'trialing'], true)) {
            $planSlug = $stripeSubscription->metadata['plan_slug'] ?? null;
            $plan = $planSlug ? Plan::query()->where('slug', $planSlug)->first() : null;
            $this->activateFromStripeSubscription($organization, $stripeSubscription, $plan);

            return;
        }

        if (in_array($stripeSubscription->status, ['past_due', 'unpaid'], true)) {
            $this->markPastDue($organization);
        }
    }

    protected function handleSubscriptionDeleted(object $stripeSubscription): void
    {
        $organization = $this->resolveOrganizationFromStripeSubscription($stripeSubscription);

        if ($organization === null) {
            return;
        }

        $this->markCancelled($organization);
    }

    protected function handlePaymentFailed(object $invoice): void
    {
        if ($invoice->subscription === null) {
            return;
        }

        $stripeSubscription = $this->stripe->subscriptions->retrieve($invoice->subscription);
        $organization = $this->resolveOrganizationFromStripeSubscription($stripeSubscription);

        if ($organization === null) {
            return;
        }

        $this->markPastDue($organization);
    }

    protected function resolveOrganizationFromCheckout(object $session): ?Organization
    {
        $organizationId = $session->metadata['organization_id']
            ?? $session->client_reference_id
            ?? null;

        if ($organizationId !== null) {
            return Organization::query()->find($organizationId);
        }

        if ($session->customer !== null) {
            return Organization::query()
                ->where('stripe_customer_id', $session->customer)
                ->first();
        }

        return null;
    }

    protected function resolveOrganizationFromStripeSubscription(object $stripeSubscription): ?Organization
    {
        $organizationId = $stripeSubscription->metadata['organization_id'] ?? null;

        if ($organizationId !== null) {
            return Organization::query()->find($organizationId);
        }

        return Organization::query()
            ->whereHas('subscription', fn ($query) => $query->where('stripe_subscription_id', $stripeSubscription->id))
            ->first();
    }

    protected function ensureStripeCustomer(Organization $organization): string
    {
        if ($organization->stripe_customer_id !== null) {
            return $organization->stripe_customer_id;
        }

        try {
            $customer = $this->stripe->customers->create([
                'email' => $organization->email,
                'name' => $organization->name,
                'metadata' => [
                    'organization_id' => (string) $organization->id,
                ],
            ]);
        } catch (ApiErrorException $exception) {
            throw new RuntimeException('Unable to create Stripe customer: '.$exception->getMessage(), 0, $exception);
        }

        $organization->forceFill(['stripe_customer_id' => $customer->id])->save();

        return $customer->id;
    }

    protected function priceIdForPlan(string $planSlug, string $interval): string
    {
        $priceId = config("stripe.prices.{$planSlug}.{$interval}");

        if ($priceId === null || $priceId === '') {
            throw new RuntimeException("Stripe price is not configured for {$planSlug} ({$interval}).");
        }

        return $priceId;
    }

    protected function normalizeInterval(string $interval): string
    {
        return match ($interval) {
            'month', 'monthly' => 'monthly',
            'year', 'yearly' => 'yearly',
            default => throw new RuntimeException('Invalid billing interval.'),
        };
    }

    protected function intervalFromStripe(object $stripeSubscription): string
    {
        $interval = $stripeSubscription->items->data[0]->price->recurring->interval ?? 'month';

        return $interval === 'year' ? 'yearly' : 'monthly';
    }

    protected function ensureConfigured(): void
    {
        if (! $this->isConfigured()) {
            throw new RuntimeException(
                'Stripe is not configured. Set STRIPE_SECRET and price IDs in your environment.',
            );
        }
    }
}
