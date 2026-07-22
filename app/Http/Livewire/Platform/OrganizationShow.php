<?php

namespace App\Http\Livewire\Platform;

use App\Services\Web\PlatformApiClient;
use Livewire\Component;

class OrganizationShow extends Component
{
    public int $organizationId;

    public $organization = [];

    public $form = [
        'status' => '',
        'plan' => '',
    ];

    public function mount(int $id): void
    {
        $this->organizationId = $id;
        $this->loadOrganization();
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
            $message = $response['error'];
            if (! empty($response['errors'])) {
                $first = collect($response['errors'])->flatten()->first();
                if (is_string($first) && $first !== '') {
                    $message = $first;
                }
            }

            $this->dispatch('toast', message: $message, type: 'error');

            return;
        }

        $this->organization = $response['data'] ?? [];
        $this->form = [
            'status' => $this->organization['status'] ?? $this->form['status'],
            'plan' => $this->organization['plan'] ?? $this->form['plan'],
        ];

        $this->dispatch('toast', message: 'Organization updated successfully.', type: 'success');
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
