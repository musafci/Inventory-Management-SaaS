<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Http\Livewire\Concerns\InteractsWithOrganizationSession;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use App\Services\Web\ApiClient;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Users extends Component
{
    use EnsuresPermission;
    use InteractsWithOrganizationSession;
    use MapsFormValidationAttributes;

    public $items = [];

    public $pagination = [];

    public $showModal = false;

    public $editingId = null;

    public $form = [
        'name' => '',
        'email' => '',
        'password' => '',
        'phone' => '',
        'role' => 'Viewer',
    ];

    public $roles = [];

    public function mount()
    {
        $this->ensurePermission('settings.manage_users');

        $this->loadRoles();
        $this->loadItems();
    }

    public function loadRoles()
    {
        try {
            $api = new ApiClient();
            $response = $api->get('/v1/roles');
            $this->roles = collect($response['data'] ?? [])
                ->reject(fn (array $role): bool => (bool) ($role['is_protected'] ?? false))
                ->pluck('name')
                ->values()
                ->all();
        } catch (\Exception) {
            $this->roles = array_keys(RolesAndPermissionsSeeder::rolePermissionMap());
        }
    }

    public function loadItems()
    {
        try {
            $api = new ApiClient();
            $response = $api->get('/v1/users', ['per_page' => 50]);
            $this->items = $response['data'] ?? [];
            $this->pagination = $response['meta']['pagination'] ?? [];
        } catch (\Exception) {
            $this->items = [];
            $this->pagination = [];
        }
    }

    public function openModal()
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm()
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'email' => '',
            'password' => '',
            'phone' => '',
            'role' => 'Viewer',
        ];
    }

    public function edit($id)
    {
        $member = collect($this->items)->firstWhere('id', (int) $id);

        if (! $member) {
            return;
        }

        $this->editingId = $member['id'];
        $this->form = [
            'name' => $member['name'] ?? '',
            'email' => $member['email'] ?? '',
            'password' => '',
            'phone' => $member['phone'] ?? '',
            'role' => $member['role'] ?? 'Viewer',
        ];
        $this->showModal = true;
    }

    public function rules(): array
    {
        if ($this->editingId) {
            return [
                'form.role' => 'required|string',
            ];
        }

        return [
            'form.name' => 'required|string|max:255',
            'form.email' => 'required|email',
            'form.password' => 'required|string|min:8',
            'form.role' => 'required|string',
        ];
    }

    public function save()
    {
        $api = new ApiClient();

        $this->validate();

        if ($this->editingId) {
            $response = $api->put("/v1/users/{$this->editingId}", [
                'role' => $this->form['role'],
            ]);
        } else {
            $response = $api->post('/v1/users', $this->form);
        }

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->dispatch('toast', message: $this->editingId ? 'Member updated.' : 'Member invited.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function destroy($id)
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/users/{$id}");

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

        $this->dispatch('toast', message: 'Member removed.', type: 'success');
        $this->loadItems();
    }

    public function render()
    {
        return view('livewire.users.index');
    }
}
