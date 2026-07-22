<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Services\Web\ApiClient;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Roles extends Component
{
    use EnsuresPermission;

    public $items = [];

    public $permissionGroups = [];

    public $showModal = false;

    public $editingId = null;

    public $form = [
        'name' => '',
        'description' => '',
        'permissions' => [],
    ];

    public function mount(): void
    {
        $this->ensurePermission('settings.manage_roles');
        $this->loadItems();
        $this->loadPermissionGroups();
    }

    public function loadItems(): void
    {
        $api = new ApiClient();
        $response = $api->get('/v1/roles');

        $this->items = $response['data'] ?? [];
    }

    public function loadPermissionGroups(): void
    {
        $api = new ApiClient();
        $response = $api->get('/v1/roles/permissions');

        $this->permissionGroups = $response['data'] ?? [];
    }

    public function openModal(): void
    {
        $this->resetForm();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function resetForm(): void
    {
        $this->editingId = null;
        $this->form = [
            'name' => '',
            'description' => '',
            'permissions' => [],
        ];
    }

    public function edit($id): void
    {
        $role = collect($this->items)->firstWhere('id', (int) $id);

        if (! $role || ($role['is_protected'] ?? false)) {
            return;
        }

        $this->editingId = $role['id'];
        $this->form = [
            'name' => $role['name'] ?? '',
            'description' => $role['description'] ?? '',
            'permissions' => $role['permissions'] ?? [],
        ];
        $this->showModal = true;
    }

    public function togglePermission(string $permission): void
    {
        if (in_array($permission, $this->form['permissions'], true)) {
            $this->form['permissions'] = array_values(array_diff($this->form['permissions'], [$permission]));
        } else {
            $this->form['permissions'][] = $permission;
        }
    }

    public function save(): void
    {
        $api = new ApiClient();

        $payload = [
            'name' => $this->form['name'],
            'description' => $this->form['description'] ?: null,
            'permissions' => $this->form['permissions'],
        ];

        if ($this->editingId) {
            $response = $api->patch("/v1/roles/{$this->editingId}", $payload);
        } else {
            $response = $api->post('/v1/roles', $payload);
        }

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->dispatch('toast', message: $this->editingId ? 'Role updated.' : 'Role created.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function destroy($id): void
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/roles/{$id}");

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

        $this->dispatch('toast', message: 'Role deleted.', type: 'success');
        $this->loadItems();
    }

    public function render()
    {
        return view('livewire.settings.roles');
    }
}
