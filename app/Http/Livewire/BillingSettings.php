<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Http\Livewire\Concerns\InteractsWithOrganizationSession;
use App\Services\Web\ApiClient;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class BillingSettings extends Component
{
    use EnsuresPermission;
    use InteractsWithOrganizationSession;

    public array $billing = [];

    public array $organization = [];

    public string $planSlug = 'growth';

    public string $interval = 'monthly';

    public ?string $checkoutStatus = null;

    public function mount(): void
    {
        $this->ensurePermission('settings.update');
        $this->checkoutStatus = request()->query('checkout');
        $this->loadBilling();
        $this->loadOrganization();
    }

    public function loadBilling(): void
    {
        $api = new ApiClient();
        $response = $api->get('/v1/billing');

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->billing = $response['data'] ?? [];

        $available = collect($this->billing['available_plans'] ?? []);

        if ($available->isNotEmpty() && ! $available->contains('slug', $this->planSlug)) {
            $this->planSlug = (string) $available->value('slug');
        }
    }

    public function loadOrganization(): void
    {
        $api = new ApiClient();
        $response = $api->get('/v1/organization');

        if (isset($response['error'])) {
            return;
        }

        $this->organization = $response['data'] ?? [];
    }

    public function checkout(): void
    {
        $api = new ApiClient();
        $response = $api->post('/v1/billing/checkout', [
            'plan_slug' => $this->planSlug,
            'interval' => $this->interval,
        ]);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $url = $response['data']['url'] ?? null;

        if ($url === null) {
            $this->dispatch('toast', message: 'Unable to start checkout.', type: 'error');

            return;
        }

        $this->redirect($url);
    }

    public function manageSubscription(): void
    {
        $api = new ApiClient();
        $response = $api->post('/v1/billing/portal');

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $url = $response['data']['url'] ?? null;

        if ($url === null) {
            $this->dispatch('toast', message: 'Unable to open billing portal.', type: 'error');

            return;
        }

        $this->redirect($url);
    }

    public function render()
    {
        $subscription = $this->billing['subscription'] ?? null;
        $availablePlans = $this->billing['available_plans'] ?? [];
        $planSlug = $subscription['plan']['slug'] ?? 'growth';
        $status = $subscription['status'] ?? 'trial';
        $needsUpgrade = in_array($status, ['trial', 'expired', 'past_due'], true);

        $selectedPlan = collect($availablePlans)->firstWhere('slug', $this->planSlug);
        $limits = $needsUpgrade
            ? ($selectedPlan['limits'] ?? ($subscription['plan']['limits'] ?? []))
            : ($subscription['plan']['limits'] ?? []);

        return view('livewire.settings.billing', [
            'subscription' => $subscription,
            'availablePlans' => $availablePlans,
            'stripeConfigured' => $this->billing['stripe_configured'] ?? false,
            'currentPlanSlug' => $planSlug,
            'planName' => $subscription['plan']['name'] ?? ucfirst($planSlug),
            'status' => $status,
            'needsUpgrade' => $needsUpgrade,
            'limits' => $limits,
        ]);
    }
}
