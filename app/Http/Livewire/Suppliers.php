<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class Suppliers extends Component
{
    use EnsuresPermission;
    use MapsFormValidationAttributes;

    public $items = [];
    public $pagination = [];
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 15;

    public $showModal = false;
    public $editingId = null;

    public $form = [
        'name' => '',
        'contact_person' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
    ];

    protected $listeners = ['deleteConfirmed' => 'destroy'];

    public function mount()
    {
        $this->ensurePermission('suppliers.view');

        $this->loadItems();

        if (request()->routeIs('suppliers.create')) {
            $this->openModal();
        }
    }

    public function loadItems()
    {
        $api = new ApiClient();
        $params = [
            'per_page' => $this->perPage,
            'sort' => ($this->sortDirection === 'desc' ? '-' : '') . $this->sortField,
        ];
        if ($this->search) {
            $params['search'] = $this->search;
        }
        $response = $api->get('/v1/suppliers', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function updatedSearch()
    {
        $this->loadItems();
    }

    public function sortBy($field)
    {
        if ($this->sortField === $field) {
            $this->sortDirection = $this->sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            $this->sortField = $field;
            $this->sortDirection = 'asc';
        }
        $this->loadItems();
    }

    public function goToPage($page)
    {
        $api = new ApiClient();
        $params = [
            'page' => $page,
            'per_page' => $this->perPage,
            'sort' => ($this->sortDirection === 'desc' ? '-' : '') . $this->sortField,
        ];
        if ($this->search) {
            $params['search'] = $this->search;
        }
        $response = $api->get('/v1/suppliers', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function rules()
    {
        return [
            'form.name' => 'required|string|max:255',
            'form.contact_person' => 'nullable|string|max:255',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'nullable|string|max:50',
            'form.address' => 'nullable|string|max:500',
        ];
    }

    public function resetForm()
    {
        $this->form = [
            'name' => '',
            'contact_person' => '',
            'email' => '',
            'phone' => '',
            'address' => '',
        ];
        $this->editingId = null;
        $this->resetErrorBag();
    }

    public function openModal($id = null)
    {
        $this->resetForm();
        $this->showModal = true;
        if ($id) {
            $this->editingId = $id;
            $api = new ApiClient();
            $response = $api->get("/v1/suppliers/{$id}");
            $supplier = $response['data'] ?? $response;
            $this->form = [
                'name' => $supplier['name'] ?? '',
                'contact_person' => $supplier['contact_person'] ?? '',
                'email' => $supplier['email'] ?? '',
                'phone' => $supplier['phone'] ?? '',
                'address' => $supplier['address'] ?? '',
            ];
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function store()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->post('/v1/suppliers', $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Supplier created successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function update()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->put("/v1/suppliers/{$this->editingId}", $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Supplier updated successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function destroy($id)
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/suppliers/{$id}");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Supplier deleted successfully.', type: 'success');
        $this->loadItems();
    }

    public function editItem($id)
    {
        $this->openModal($id);
    }

    public function render()
    {
        return view('livewire.suppliers.index');
    }
}
