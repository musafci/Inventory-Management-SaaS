<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\WithFileUploads;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class Customers extends Component
{
    use EnsuresPermission;
    use MapsFormValidationAttributes;
    use WithFileUploads;

    public $items = [];
    public $pagination = [];
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 15;

    public $showModal = false;
    public $showImportModal = false;
    public $editingId = null;

    public $importFile;
    /** @var array<string, mixed>|null */
    public $importResult = null;

    public $form = [
        'name' => '',
        'email' => '',
        'phone' => '',
        'address' => '',
    ];

    protected $listeners = ['deleteConfirmed' => 'destroy'];

    public function mount()
    {
        $this->ensurePermission('customers.view');

        $this->loadItems();

        if (request()->routeIs('customers.create')) {
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
        $response = $api->get('/v1/customers', $params);
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
        $response = $api->get('/v1/customers', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function rules()
    {
        return [
            'form.name' => 'required|string|max:255',
            'form.email' => 'nullable|email|max:255',
            'form.phone' => 'nullable|string|max:50',
            'form.address' => 'nullable|string|max:500',
        ];
    }

    public function resetForm()
    {
        $this->form = [
            'name' => '',
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
            $response = $api->get("/v1/customers/{$id}");
            $customer = $response['data'] ?? $response;
            $this->form = [
                'name' => $customer['name'] ?? '',
                'email' => $customer['email'] ?? '',
                'phone' => $customer['phone'] ?? '',
                'address' => $customer['address'] ?? '',
            ];
        }
    }

    public function closeModal()
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function openImportModal()
    {
        $this->ensurePermission('customers.create');
        $this->importFile = null;
        $this->importResult = null;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->importFile = null;
        $this->importResult = null;
    }

    public function importCsv()
    {
        $this->ensurePermission('customers.create');
        $this->validate([
            'importFile' => 'required|file|mimes:csv,txt|max:2048',
        ]);

        $path = $this->importFile->getRealPath();
        $csv = is_string($path) ? file_get_contents($path) : false;

        if ($csv === false) {
            $this->dispatch('toast', message: 'Unable to read the CSV file.', type: 'error');

            return;
        }

        $api = new ApiClient();
        $response = $api->post('/v1/customers/import', ['csv' => $csv]);

        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');

            return;
        }

        $this->importResult = $response['data'] ?? null;
        $this->loadItems();

        $imported = (int) ($this->importResult['imported'] ?? 0);
        $failed = (int) ($this->importResult['failed'] ?? 0);

        if ($failed > 0) {
            $this->dispatch('toast', message: "Imported {$imported} customers. {$failed} rows failed.", type: 'error');
        } else {
            $this->dispatch('toast', message: "Imported {$imported} customers successfully.", type: 'success');
            $this->closeImportModal();
        }
    }

    public function store()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->post('/v1/customers', $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Customer created successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function update()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->put("/v1/customers/{$this->editingId}", $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Customer updated successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function destroy($id)
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/customers/{$id}");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Customer deleted successfully.', type: 'success');
        $this->loadItems();
    }

    public function editItem($id)
    {
        $this->openModal($id);
    }

    public function render()
    {
        return view('livewire.customers.index');
    }
}
