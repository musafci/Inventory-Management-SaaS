<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Concerns\HasApiDetailView;
use App\Http\Livewire\Concerns\MapsFormValidationAttributes;
use Livewire\Component;
use Livewire\Attributes\Layout;
use App\Services\Web\ApiClient;

#[Layout('layouts.app')]
class SalesOrders extends Component
{
    use HasApiDetailView;
    use MapsFormValidationAttributes;

    public $items = [];
    public $pagination = [];
    public $search = '';
    public $sortField = 'created_at';
    public $sortDirection = 'desc';
    public $perPage = 15;

    public $showModal = false;
    public $showFulfillModal = false;
    public $showPayModal = false;
    public $showRefundModal = false;
    public $editingId = null;

    public $customers = [];
    public $warehouses = [];
    public $products = [];

    public $form = [
        'customer_id' => '',
        'warehouse_id' => '',
        'order_date' => '',
        'items' => [],
    ];

    public $fulfillItems = [];
    public $fulfillNote = '';

    public $payAmount = '';
    public $payMethod = 'bank_transfer';
    public $payReference = '';
    public $payNote = '';

    public $refundAmount = '';
    public $refundMethod = 'bank_transfer';
    public $refundReference = '';
    public $refundNote = '';
    public $refundItems = [];

    protected $listeners = ['deleteConfirmed' => 'destroy'];

    public function mount()
    {
        $this->mountDetailRoute('sales-orders.show', '/v1/sales-orders', '/sales-orders');

        if (! $this->isDetailView()) {
            $this->loadDropdowns();
            $this->loadItems();
        }

        $routeName = request()->route()?->getName();
        if ($routeName === 'sales-orders.create') {
            $this->openModal();
        } elseif ($routeName === 'sales-orders.edit' && request()->route('id')) {
            $this->openModal(request()->route('id'));
        }
    }

    public function loadDropdowns()
    {
        $api = new ApiClient();
        $customers = $api->get('/v1/customers', ['per_page' => 200]);
        $warehouses = $api->get('/v1/warehouses', ['per_page' => 200]);
        $products = $api->get('/v1/products', ['per_page' => 200]);
        $this->customers = $customers['data'] ?? [];
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
        $response = $api->get('/v1/sales-orders', $params);
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
        $response = $api->get('/v1/sales-orders', $params);
        $this->items = $response['data'] ?? [];
        $this->pagination = $response['meta']['pagination'] ?? [];
    }

    public function rules()
    {
        return [
            'form.customer_id' => 'required',
            'form.warehouse_id' => 'required',
            'form.order_date' => 'required|date',
            'form.items' => 'required|array|min:1',
            'form.items.*.product_id' => 'required',
            'form.items.*.quantity' => 'required|integer|min:1',
            'form.items.*.unit_price' => 'required|numeric|min:0',
        ];
    }

    public function addItem()
    {
        $this->form['items'][] = [
            'product_id' => '',
            'quantity' => '',
            'unit_price' => '',
            'discount' => '0',
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
            'customer_id' => '',
            'warehouse_id' => '',
            'order_date' => '',
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
            $response = $api->get("/v1/sales-orders/{$id}");
            $order = $response['data'] ?? $response;
            $items = [];
            foreach ($order['items'] ?? [] as $item) {
                $items[] = [
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'discount' => $item['discount'] ?? '0',
                ];
            }
            $this->form = [
                'customer_id' => $order['customer_id'] ?? '',
                'warehouse_id' => $order['warehouse_id'] ?? '',
                'order_date' => $order['order_date'] ?? '',
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
        $response = $api->post('/v1/sales-orders', $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Sales order created successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function update()
    {
        $this->validate();
        $api = new ApiClient();
        $response = $api->put("/v1/sales-orders/{$this->editingId}", $this->form);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Sales order updated successfully.', type: 'success');
        $this->closeModal();
        $this->loadItems();
    }

    public function destroy($id)
    {
        $api = new ApiClient();
        $response = $api->delete("/v1/sales-orders/{$id}");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Sales order deleted successfully.', type: 'success');
        $this->loadItems();
    }

    public function editItem($id)
    {
        $this->openModal($id);
    }

    public function confirmOrder($id)
    {
        $api = new ApiClient();
        $response = $api->post("/v1/sales-orders/{$id}/confirm");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Sales order confirmed.', type: 'success');
        $this->loadItems();
    }

    public function cancel($id)
    {
        $api = new ApiClient();
        $response = $api->post("/v1/sales-orders/{$id}/cancel");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Sales order cancelled.', type: 'success');
        $this->loadItems();
    }

    public function openFulfillModal($id)
    {
        $this->editingId = $id;
        $api = new ApiClient();
        $response = $api->get("/v1/sales-orders/{$id}");
        $order = $response['data'] ?? $response;
        $this->fulfillItems = [];
        foreach ($order['items'] ?? [] as $item) {
            $remaining = ($item['quantity'] ?? 0) - ($item['quantity_fulfilled'] ?? 0);
            if ($remaining > 0) {
                $this->fulfillItems[] = [
                    'sales_order_item_id' => $item['id'],
                    'quantity' => $remaining,
                ];
            }
        }
        $this->fulfillNote = '';
        $this->showFulfillModal = true;
    }

    public function closeFulfillModal()
    {
        $this->showFulfillModal = false;
        $this->fulfillItems = [];
        $this->fulfillNote = '';
        $this->editingId = null;
    }

    public function submitFulfill()
    {
        $api = new ApiClient();
        $response = $api->post("/v1/sales-orders/{$this->editingId}/fulfill", [
            'items' => $this->fulfillItems,
            'note' => $this->fulfillNote ?: null,
        ]);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Order fulfilled successfully.', type: 'success');
        $this->closeFulfillModal();
        $this->loadItems();
    }

    public function deliverOrder($id)
    {
        $api = new ApiClient();
        $response = $api->post("/v1/sales-orders/{$id}/deliver");
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Order marked as delivered.', type: 'success');
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
        $response = $api->post("/v1/sales-orders/{$this->editingId}/pay", [
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

    public function openRefundModal($id)
    {
        $this->editingId = $id;
        $api = new ApiClient();
        $response = $api->get("/v1/sales-orders/{$id}");
        $order = $response['data'] ?? $response;
        $this->refundItems = [];
        foreach ($order['items'] ?? [] as $item) {
            $returned = $item['quantity_returned'] ?? 0;
            $fulfilled = $item['quantity_fulfilled'] ?? 0;
            $canReturn = $fulfilled - $returned;
            if ($canReturn > 0) {
                $this->refundItems[] = [
                    'sales_order_item_id' => $item['id'],
                    'quantity' => $canReturn,
                ];
            }
        }
        $this->refundAmount = '';
        $this->refundMethod = 'bank_transfer';
        $this->refundReference = '';
        $this->refundNote = '';
        $this->showRefundModal = true;
    }

    public function closeRefundModal()
    {
        $this->showRefundModal = false;
        $this->refundItems = [];
        $this->refundAmount = '';
        $this->refundMethod = 'bank_transfer';
        $this->refundReference = '';
        $this->refundNote = '';
        $this->editingId = null;
    }

    public function submitRefund()
    {
        $api = new ApiClient();
        $response = $api->post("/v1/sales-orders/{$this->editingId}/refund", [
            'amount' => $this->refundAmount,
            'method' => $this->refundMethod,
            'reference' => $this->refundReference ?: null,
            'note' => $this->refundNote ?: null,
            'return_items' => $this->refundItems,
        ]);
        if (isset($response['error'])) {
            $this->dispatch('toast', message: $response['error'], type: 'error');
            return;
        }
        $this->dispatch('toast', message: 'Refund processed successfully.', type: 'success');
        $this->closeRefundModal();
        $this->loadItems();
    }

    public function render()
    {
        return view('livewire.sales-orders.index');
    }
}
