<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\InteractsWithOrganizationSession;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use App\Services\Web\ApiClient;
use Database\Seeders\RolesAndPermissionsSeeder;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.app')]
class Users extends Component
{
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

    protected $listeners = ['deleteConfirmed' => 'destroy'];

    public function mount()
    {
        if (! $this->canManageUsers()) {
            abort(403, 'You do not have permission to manage team members.');
        }

        $this->roles = array_keys(RolesAndPermissionsSeeder::rolePermissionMap());
        $this->loadItems();
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

    public function confirmDelete($id)
    {
        $member = collect($this->items)->firstWhere('id', (int) $id);
        $name = $member['name'] ?? 'this user';

        $this->dispatch('confirm', [
            'title' => 'Remove member',
            'message' => "Remove {$name} from this organization?",
            'confirmEvent' => 'deleteConfirmed',
            'confirmPayload' => $id,
            'type' => 'danger',
        ]);
    }

    public function destroy($id)
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/users/{$id}");

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

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
