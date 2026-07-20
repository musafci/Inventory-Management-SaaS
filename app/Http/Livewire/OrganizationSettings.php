<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\InteractsWithOrganizationSession;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use App\Services\Web\ApiClient;
use App\Services\Web\WebSessionService;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class OrganizationSettings extends Component
{
    use InteractsWithOrganizationSession;
    use MapsFormValidationAttributes;

    public $organization = [];

    public $form = [
        'name' => '',
        'email' => '',
        'phone' => '',
    ];

    public function mount(): void
    {
        if (! $this->canManageOrganization()) {
            abort(403, 'Only the organization owner can access organization settings.');
        }

        $this->loadOrganization();
    }

    public function loadOrganization(): void
    {
        $api = new ApiClient();
        $response = $api->get('/v1/organization');

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->organization = $response['data'] ?? [];
        $this->form = [
            'name' => $this->organization['name'] ?? '',
            'email' => $this->organization['email'] ?? '',
            'phone' => $this->organization['phone'] ?? '',
        ];
    }

    public function rules(): array
    {
        return [
            'form.name' => 'required|string|max:255',
            'form.email' => 'required|email|max:255',
            'form.phone' => 'nullable|string|max:50',
        ];
    }

    protected function customValidationAttributes(): array
    {
        return [
            'form.name' => 'organization name',
        ];
    }

    public function save(): void
    {
        $this->validate();

        $api = new ApiClient();
        $response = $api->patch('/v1/organization', $this->form);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->organization = $response['data'] ?? [];
        app(WebSessionService::class)->syncOrganization($this->organization);

        $this->dispatch('toast', message: 'Organization settings saved.', type: 'success');
    }

    public function render()
    {
        return view('livewire.settings.organization');
    }
}
