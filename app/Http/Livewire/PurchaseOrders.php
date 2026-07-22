<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\EnsuresPermission;
use App\Http\Livewire\Concerns\HasApiDetailView;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class PurchaseOrders extends Component
{
    use EnsuresPermission;
    use HasApiDetailView;
    use MapsFormValidationAttributes;

    public $items = [];
    public $pagination = [];
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 15;

    public $showModal = false;
    public $showReceiveModal = false;
    public $showPayModal = false;
    public $editingId = null;

    public $suppliers = [];
    public $warehouses = [];
    public $products = [];

    public $form = [
        'supplier_id' => '',
        'warehouse_id' => '',
        'order_date' => '',
        'expected_date' => '',
        'items' => [],
    ];

    public $receiveItems = [];
    public $receiveNote = '';

    public $payAmount = '';
    public $payMethod = 'bank_transfer';
    public $payReference = '';
    public $payNote = '';

    protected $listeners = ['deleteConfirmed' => 'destroy'];

    public function mount()
    {
        $this->ensurePermission('orders.purchase.view');

        $this->mountDetailRoute('purchase-orders.show', '/v1/purchase-orders', '/purchase-orders');

        if (! $this->isDetailView()) {
            $this->loadDropdowns();
            $this->loadItems();
        }

        $routeName = request()->route()?->getName();
        if ($routeName === 'purchase-orders.create') {
            $this->openModal();
        } elseif ($routeName === 'purchase-orders.edit' && request()->route('id')) {
            $this->openModal(request()->route('id'));
        }
    }

    public function loadDropdowns()
    {
        $api = new ApiClient();
        $suppliers = $api->get('/v1/suppliers', ['per_page' => 200]);
        $warehouses = $api->get('/v1/warehouses', ['per_page' => 200]);
        $products = $api->get('/v1/products', ['per_page' => 200]);
        $this->suppliers = $suppliers['data'] ?? [];
        $this->warehouses = $warehouses['data'] ?? [];
        $this->products = $products['data'] ?? [];
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
        $response = $api->get('/v1/purchase-orders', $params);
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
        $response = $api->get('/v1/purchase-orders', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function rules()
    {
        return [
            'form.supplier_id' => 'required',
            'form.warehouse_id' => 'required',
            'form.order_date' => 'required|date',
            'form.expected_date' => 'nullable|date',
            'form.items' => 'required|array|min:1',
            'form.items.*.product_id' => 'required',
            'form.items.*.quantity_ordered' => 'required|integer|min:1',
            'form.items.*.unit_cost' => 'required|numeric|min:0',
        ];
    }

    public function addItem()
    {
        $this->form['items'][] = [
            'product_id' => '',
            'quantity_ordered' => '',
            'unit_cost' => '',
        ];
    }

    public function removeItem($index)
    {
        unset($this->form['items'][$index]);
        $this->form['items'] = array_values($this->form['items']);
    }

    public function resetForm()
    {
        $this->form = [
            'supplier_id' => '',
            'warehouse_id' => '',
            'order_date' => '',
            'expected_date' => '',
            'items' => [],
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
            $response = $api->get("/v1/purchase-orders/{$id}");
            $order = $response['data'] ?? $response;
            $items = [];
            foreach ($order['items'] ?? [] as $item) {
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity_ordered' => $item['quantity_ordered'],
                    'unit_cost' => $item['unit_cost'],
                ];
            }
            $this->form = [
                'supplier_id' => $order['supplier_id'] ?? '',
                'warehouse_id' => $order['warehouse_id'] ?? '',
                'order_date' => $order['order_date'] ?? '',
                'expected_date' => $order['expected_date'] ?? '',
                'items' => $items,
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
        $response = $api->post('/v1/purchase-orders', $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Purchase order created successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function update()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->put("/v1/purchase-orders/{$this->editingId}", $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Purchase order updated successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function destroy($id)
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/purchase-orders/{$id}");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Purchase order deleted successfully.', type: 'success');
        $this->loadItems();
    }

    public function editItem($id)
    {
        $this->openModal($id);
    }

    public function send($id)
    {
        $api = new ApiClient();
        $response = $api->post("/v1/purchase-orders/{$id}/send");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Purchase order sent successfully.', type: 'success');
        $this->loadItems();
    }

    public function cancel($id)
    {
        $api = new ApiClient();
        $response = $api->post("/v1/purchase-orders/{$id}/cancel");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Purchase order cancelled.', type: 'success');
        $this->loadItems();
    }

    public function openReceiveModal($id)
    {
        $this->editingId = $id;
        $api = new ApiClient();
        $response = $api->get("/v1/purchase-orders/{$id}");
        $order = $response['data'] ?? $response;
        $this->receiveItems = [];
        foreach ($order['items'] ?? [] as $item) {
            $remaining = ($item['quantity_ordered'] ?? 0) - ($item['quantity_received'] ?? 0);
            if ($remaining > 0) {
                $this->receiveItems[] = [
                    'purchase_order_item_id' => $item['id'],
                    'quantity' => $remaining,
                ];
            }
        }
        $this->receiveNote = '';
        $this->showReceiveModal = true;
    }

    public function closeReceiveModal()
    {
        $this->showReceiveModal = false;
        $this->receiveItems = [];
        $this->receiveNote = '';
        $this->editingId = null;
    }

    public function submitReceive()
    {
        $api = new ApiClient();
        $response = $api->post("/v1/purchase-orders/{$this->editingId}/receive", [
            'items' => $this->receiveItems,
            'note' => $this->receiveNote ?: null,
        ]);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Goods received successfully.', type: 'success');
        $this->closeReceiveModal();
        $this->loadItems();
    }

    public function openPayModal($id)
    {
        $this->editingId = $id;
        $this->payAmount = '';
        $this->payMethod = 'bank_transfer';
        $this->payReference = '';
        $this->payNote = '';
        $this->showPayModal = true;
    }

    public function closePayModal()
    {
        $this->showPayModal = false;
        $this->payAmount = '';
        $this->payMethod = 'bank_transfer';
        $this->payReference = '';
        $this->payNote = '';
        $this->editingId = null;
    }

    public function submitPay()
    {
        $api = new ApiClient();
        $response = $api->post("/v1/purchase-orders/{$this->editingId}/pay", [
            'amount' => $this->payAmount,
            'method' => $this->payMethod,
            'reference' => $this->payReference ?: null,
            'note' => $this->payNote ?: null,
        ]);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Payment recorded successfully.', type: 'success');
        $this->closePayModal();
        $this->loadItems();
    }

    public function render()
    {
        return view('livewire.purchase-orders.index');
    }
}
