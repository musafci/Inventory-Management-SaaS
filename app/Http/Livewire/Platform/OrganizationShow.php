<?php

namespace App\Http\Livewire\Platform;

use App\Services\Web\PlatformApiClient;
use Livewire\Component;

class OrganizationShow extends Component
{
    public int $organizationId;

    public $organization = [];

    public $plans = [];

    public $supportNotes = [];

    public $featureFlags = [];

    public $form = [
        'status' => '',
        'plan' => '',
    ];

    public $subscriptionForm = [
        'plan_id' => '',
        'status' => 'active',
    ];

    public $noteForm = [
        'note' => '',
    ];

    public $impersonationForm = [
        'user_id' => '',
        'reason' => '',
    ];

    public function mount(int $id): void
    {
        $this->organizationId = $id;
        $this->loadPlans();
        $this->loadOrganization();
        $this->loadSupportNotes();
        $this->loadFeatureFlags();
    }

    public function loadPlans(): void
    {
        $api = new PlatformApiClient();
        $response = $api->get('/plans');
        $this->plans = $response['data'] ?? [];
    }

    public function loadOrganization(): void
    {
        $api = new PlatformApiClient();
        $response = $api->get("/organizations/{$this->organizationId}");

        if (isset($response['error'])) {
            if (($response['status'] ?? 0) === 404) {
                abort(404);
            }

            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->organization = $response['data'] ?? [];
        $this->form = [
            'status' => $this->organization['status'] ?? 'trial',
            'plan' => $this->organization['plan'] ?? 'trial',
        ];

        $subscription = $this->organization['subscription'] ?? null;
        $this->subscriptionForm = [
            'plan_id' => (string) ($subscription['plan_id'] ?? ''),
            'status' => $subscription['status'] ?? 'trial',
        ];
    }

    public function loadSupportNotes(): void
    {
        $api = new PlatformApiClient();
        $response = $api->get("/organizations/{$this->organizationId}/support-notes");
        $this->supportNotes = $response['data'] ?? [];
    }

    public function loadFeatureFlags(): void
    {
        $api = new PlatformApiClient();
        $response = $api->get("/organizations/{$this->organizationId}/feature-flags");
        $this->featureFlags = $response['data'] ?? [];
    }

    public function applyStatus(string $status): void
    {
        $this->form['status'] = $status;
        $this->save();
    }

    public function save(): void
    {
        $this->validate([
            'form.status' => 'required|in:trial,active,suspended',
            'form.plan' => 'required|string|max:50',
        ]);

        $api = new PlatformApiClient();
        $response = $api->patch("/organizations/{$this->organizationId}", $this->form);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $this->extractErrorMessage($response), type: 'error');

            return;
        }

        $this->organization = $response['data'] ?? [];
        $this->dispatch('toast', message: 'Organization updated successfully.', type: 'success');
    }

    public function saveSubscription(): void
    {
        $this->validate([
            'subscriptionForm.plan_id' => 'required|integer',
            'subscriptionForm.status' => 'required|in:trial,active,past_due,cancelled',
        ]);

        $api = new PlatformApiClient();
        $response = $api->patch("/organizations/{$this->organizationId}/subscription", [
            'plan_id' => (int) $this->subscriptionForm['plan_id'],
            'status' => $this->subscriptionForm['status'],
        ]);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $this->extractErrorMessage($response), type: 'error');

            return;
        }

        $this->loadOrganization();
        $this->dispatch('toast', message: 'Subscription updated.', type: 'success');
    }

    public function addSupportNote(): void
    {
        $this->validate([
            'noteForm.note' => 'required|string|max:5000',
        ]);

        $api = new PlatformApiClient();
        $response = $api->post("/organizations/{$this->organizationId}/support-notes", $this->noteForm);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $this->extractErrorMessage($response), type: 'error');

            return;
        }

        $this->noteForm['note'] = '';
        $this->loadSupportNotes();
        $this->dispatch('toast', message: 'Support note added.', type: 'success');
    }

    public function toggleFeatureFlag(int $flagId, bool $enabled): void
    {
        $api = new PlatformApiClient();
        $response = $api->patch("/organizations/{$this->organizationId}/feature-flags/{$flagId}", [
            'enabled' => $enabled,
        ]);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $this->extractErrorMessage($response), type: 'error');

            return;
        }

        $this->loadFeatureFlags();
        $this->dispatch('toast', message: 'Feature flag updated.', type: 'success');
    }

    public function startImpersonation(): void
    {
        $this->validate([
            'impersonationForm.user_id' => 'required|integer',
            'impersonationForm.reason' => 'required|string|min:10|max:500',
        ]);

        $api = new PlatformApiClient();
        $response = $api->post("/organizations/{$this->organizationId}/impersonate", [
            'user_id' => (int) $this->impersonationForm['user_id'],
            'reason' => $this->impersonationForm['reason'],
        ]);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $this->extractErrorMessage($response), type: 'error');

            return;
        }

        $token = $response['data']['token']['access_token'] ?? null;
        $orgId = $response['data']['organization_id'] ?? $this->organizationId;

        if ($token === null) {
            $this->dispatch('toast', message: 'Impersonation token was not returned.', type: 'error');

            return;
        }

        $this->impersonationForm = ['user_id' => '', 'reason' => ''];
        $this->dispatch('toast', message: 'Impersonation session started. Token issued — use tenant API with X-Organization-Id.', type: 'success');

        session()->flash('impersonation_token', $token);
        session()->flash('impersonation_org_id', $orgId);
    }

    /**
     * @param  array<string, mixed>  $response
     */
    protected function extractErrorMessage(array $response): string
    {
        $message = $response['error'] ?? 'Request failed';

        if (! empty($response['errors'])) {
            $first = collect($response['errors'])->flatten()->first();
            if (is_string($first) && $first !== '') {
                return $first;
            }
        }

        return is_string($message) ? $message : 'Request failed';
    }

    public function render()
    {
        return view('livewire.platform.organization-show')
            ->layout('layouts.platform', [
                'heading' => $this->organization['name'] ?? 'Organization',
                'title' => $this->organization['name'] ?? 'Organization',
            ]);
    }
}
