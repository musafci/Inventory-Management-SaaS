<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class StockMovements extends Component
{
    use EnsuresPermission;
    use MapsFormValidationAttributes;

    public $items = [];
    public $pagination = [];
    public $search = '';
    public $perPage = 15;

    public $showModal = false;

    public $warehouses = [];
    public $products = [];

    public $form = [
        'warehouse_id' => '',
        'product_id' => '',
        'type' => 'adjustment_in',
        'quantity' => '',
        'note' => '',
    ];

    public function mount()
    {
        $this->ensurePermission('inventory.view');

        $this->loadDropdowns();
        $this->loadItems();

        if (request()->routeIs('stock-movements.create')) {
            $this->openModal();
        }
    }

    public function loadDropdowns()
    {
        $api = new ApiClient();
        $warehouses = $api->get('/v1/warehouses', ['per_page' => 200]);
        $products = $api->get('/v1/products', ['per_page' => 200, 'is_active' => true]);
        $this->warehouses = $warehouses['data'] ?? [];
        $this->products = $products['data'] ?? [];
    }

    public function loadItems()
    {
        $api = new ApiClient();
        $params = [
            'per_page' => $this->perPage,
        ];
        if ($this->search) {
            $params['search'] = $this->search;
        }
        $response = $api->get('/v1/stock-movements', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function updatedSearch()
    {
        $this->loadItems();
    }

    public function goToPage($page)
    {
        $api = new ApiClient();
        $params = [
            'page' => $page,
            'per_page' => $this->perPage,
        ];
        if ($this->search) {
            $params['search'] = $this->search;
        }
        $response = $api->get('/v1/stock-movements', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function rules()
    {
        return [
            'form.warehouse_id' => 'required',
            'form.product_id' => 'required',
            'form.type' => 'required|in:adjustment_in,adjustment_out,transfer_in,transfer_out',
            'form.quantity' => 'required|integer|min:1',
            'form.note' => 'nullable|string|max:500',
        ];
    }

    public function resetForm()
    {
        $this->form = [
            'warehouse_id' => '',
            'product_id' => '',
            'type' => 'adjustment_in',
            'quantity' => '',
            'note' => '',
        ];
        $this->resetErrorBag();
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

    public function store()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->post('/v1/stock-movements', $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Stock movement recorded successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function render()
    {
        return view('livewire.stock-movements.index');
    }
}
