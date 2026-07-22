<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Http\Livewire\Concerns\HasApiDetailView;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class Products extends Component
{
    use EnsuresPermission;
    use HasApiDetailView;
    use MapsFormValidationAttributes;

    public $items = [];
    public $pagination = [];
    public $search = '';
    public $sortField = 'name';
    public $sortDirection = 'asc';
    public $perPage = 15;

    public $showModal = false;
    public $editingId = null;

    public $categories = [];
    public $units = [];

    public $form = [
        'category_id' => '',
        'unit_id' => '',
        'name' => '',
        'sku' => '',
        'barcode' => '',
        'cost_price' => '',
        'selling_price' => '',
        'tax_rate' => '0',
        'reorder_point' => '',
        'is_active' => true,
    ];

    protected $listeners = ['deleteConfirmed' => 'destroy'];

    public function mount()
    {
        $this->ensurePermission('inventory.view');

        $this->mountDetailRoute('products.show', '/v1/products', '/products');

        if (! $this->isDetailView()) {
            $this->loadDropdowns();
            $this->loadItems();
        } else {
            $this->loadDropdowns();
        }

        $routeName = request()->route()?->getName();
        if ($routeName === 'products.create') {
            $this->openModal();
        } elseif ($routeName === 'products.edit' && request()->route('id')) {
            $this->openModal(request()->route('id'));
        }
    }

    public function loadDropdowns()
    {
        $api = new ApiClient();
        $categories = $api->get('/v1/categories', ['per_page' => 200]);
        $units = $api->get('/v1/units', ['per_page' => 200]);
        $this->categories = $categories['data'] ?? [];
        $this->units = $units['data'] ?? [];
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
        $response = $api->get('/v1/products', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function updatedSearch()
    {
        $this->resetPage();
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

    public function updatePerPage()
    {
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
        $response = $api->get('/v1/products', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function resetPage()
    {
        // Will be used before searches
    }

    public function rules()
    {
        return [
            'form.category_id' => 'required',
            'form.unit_id' => 'required',
            'form.name' => 'required|string|max:255',
            'form.sku' => 'nullable|string|max:100',
            'form.barcode' => 'nullable|string|max:100',
            'form.cost_price' => 'required|numeric|min:0',
            'form.selling_price' => 'required|numeric|min:0',
            'form.tax_rate' => 'nullable|numeric|min:0|max:100',
            'form.reorder_point' => 'nullable|integer|min:0',
            'form.is_active' => 'boolean',
        ];
    }

    public function resetForm()
    {
        $this->form = [
            'category_id' => '',
            'unit_id' => '',
            'name' => '',
            'sku' => '',
            'barcode' => '',
            'cost_price' => '',
            'selling_price' => '',
            'tax_rate' => '0',
            'reorder_point' => '',
            'is_active' => true,
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
            $response = $api->get("/v1/products/{$id}");
            $product = $response['data'] ?? $response;
            $this->form = [
                'category_id' => $product['category_id'] ?? '',
                'unit_id' => $product['unit_id'] ?? '',
                'name' => $product['name'] ?? '',
                'sku' => $product['sku'] ?? '',
                'barcode' => $product['barcode'] ?? '',
                'cost_price' => $product['cost_price'] ?? '',
                'selling_price' => $product['selling_price'] ?? '',
                'tax_rate' => $product['tax_rate'] ?? '0',
                'reorder_point' => $product['reorder_point'] ?? '',
                'is_active' => $product['is_active'] ?? true,
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
        $response = $api->post('/v1/products', $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Product created successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function update()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->put("/v1/products/{$this->editingId}", $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Product updated successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function destroy($id)
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/products/{$id}");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Product deleted successfully.', type: 'success');
        $this->loadItems();
    }

    public function editItem($id)
    {
        $this->openModal($id);
    }

    public function render()
    {
        return view('livewire.products.index');
    }
}
