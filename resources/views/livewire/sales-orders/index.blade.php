<div x-on:open-print.window="window.open($event.detail.url, '_blank')">
@if($detail)
    @include('livewire.sales-orders.show-detail')
@else
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-list-search wire:model.live.debounce.300ms="search" placeholder="Search sales orders..." />
        <button wire:click="openModal()" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            New Sales Order
        </button>
    </div>

    <div class="card overflow-hidden" wire:loading.class="wire-loading-dim" wire:target="items">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Order #</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Customer</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Order Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Subtotal</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Discount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-slate-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white" wire:transition.opacity.duration.300ms>
                    @forelse($items as $item)
                        <tr class="table-row-hover">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <a href="/sales-orders/{{ $item['id'] }}" class="text-primary-600 hover:text-primary-500">{{ $item['order_number'] ?? '-' }}</a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $item['customer']['name'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-slate-100 text-slate-700',
                                        'confirmed' => 'bg-primary-100 text-primary-700',
                                        'shipped' => 'bg-primary-100 text-primary-700',
                                        'delivered' => 'bg-emerald-100 text-emerald-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                        'refunded' => 'bg-orange-100 text-orange-700',
                                    ];
                                    $status = $item['status'] ?? 'draft';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $item['order_date'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-900">${{ number_format($item['gross_subtotal'] ?? $item['total_amount'] ?? 0, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-emerald-700">
                                @if((float) ($item['total_discount'] ?? 0) > 0)
                                    -${{ number_format($item['total_discount'], 2) }}
                                @else
                                    <span class="text-slate-400">—</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">${{ number_format($item['total_amount'] ?? 0, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-1">
                                    <a href="/sales-orders/{{ $item['id'] }}/print?print=1" target="_blank" class="action-btn" title="Print">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6.72 13.829c-.24.03-.48.062-.72.096m.72-.096a42.415 42.415 0 0110.56 0m-10.56 0L6.34 18m10.94-4.171c.24.03.48.062.72.096m-.72-.096L17.66 18M6.34 6.34l.393-.393A2.25 2.25 0 019.05 5.25h5.9a2.25 2.25 0 011.857.697l.393.393M6.34 6.34l-1.125 1.125A2.25 2.25 0 004.5 9.318v5.364a2.25 2.25 0 002.715 2.196l1.125-.281m12-8.455l1.125 1.125A2.25 2.25 0 0119.5 9.318v5.364a2.25 2.25 0 01-2.715 2.196l-1.125-.281m-12 0h12" /></svg>
                                    </a>
                                    @if(($item['status'] ?? '') === 'draft')
                                        <button wire:click="editItem({{ $item['id'] }})" class="action-btn action-btn-primary" title="Edit">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                        </button>
                                        <button @click.prevent="$store.confirm.open('Confirm Order', 'Confirm this sales order? Stock will be reserved.', 'warning', () => $wire.confirmOrder({{ $item['id'] }}), 'Confirm')" class="action-btn action-btn-primary" title="Confirm">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5" /></svg>
                                        </button>
                                    @endif
                                    @if(($item['status'] ?? '') === 'confirmed')
                                        <button wire:click="openFulfillModal({{ $item['id'] }})" class="action-btn action-btn-primary" title="Fulfill/Shipment">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M8.25 18.75a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h6m-9 0H3.375a1.125 1.125 0 01-1.125-1.125V14.25m17.25 4.5a1.5 1.5 0 01-3 0m3 0a1.5 1.5 0 00-3 0m3 0h1.125c.621 0 1.129-.504 1.09-1.124a17.902 17.902 0 00-3.213-9.193 2.056 2.056 0 00-1.58-.86H14.25M16.5 18.75h-2.25m0-11.177v-.958c0-.568-.422-1.048-.987-1.106a48.554 48.554 0 00-10.026 0 1.106 1.106 0 00-.987 1.106v7.635m12-6.677v6.677m0 4.5v-4.5m0 0h-12" /></svg>
                                        </button>
                                    @endif
                                    @if(($item['status'] ?? '') === 'shipped')
                                        <button @click.prevent="$store.confirm.open('Mark Delivered', 'Mark this order as delivered?', 'info', () => $wire.deliverOrder({{ $item['id'] }}), 'Delivered')" class="action-btn action-btn-success" title="Mark Delivered">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                        </button>
                                    @endif
                                    @if(in_array(($item['status'] ?? ''), ['confirmed', 'shipped', 'delivered']))
                                        <button wire:click="openPayModal({{ $item['id'] }})" class="action-btn action-btn-warning" title="Record Payment">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                                        </button>
                                    @endif
                                    @if(in_array(($item['status'] ?? ''), ['shipped', 'delivered']))
                                        <button wire:click="openRefundModal({{ $item['id'] }})" class="action-btn action-btn-warning" title="Refund">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3" /></svg>
                                        </button>
                                    @endif
                                    @if(in_array(($item['status'] ?? ''), ['draft', 'confirmed']))
                                        <button @click.prevent="$store.confirm.open('Cancel Order', 'Cancel this sales order?', 'danger', () => $wire.cancel({{ $item['id'] }}), 'Cancel Order')" class="action-btn action-btn-danger" title="Cancel">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    @endif
                                    @if(($item['status'] ?? '') === 'draft')
                                        <button @click.prevent="$store.confirm.open('Delete Order', 'Delete this sales order? This action cannot be undone.', 'danger', () => $wire.destroy({{ $item['id'] }}), 'Delete')" class="action-btn action-btn-danger" title="Delete">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-slate-300" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                <p class="mt-2 text-sm text-slate-500">No sales orders found.</p>
                                <button wire:click="openModal()" class="mt-3 btn-primary text-sm">New Sales Order</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!empty($pagination['last_page']) && $pagination['last_page'] > 1)
            <div class="border-t border-slate-100 bg-slate-50/50 px-4 py-3 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-slate-500">
                        Showing {{ ($pagination['current_page'] - 1) * $perPage + 1 }} to {{ min($pagination['current_page'] * $perPage, $pagination['total'] ?? 0) }} of {{ $pagination['total'] ?? 0 }} results
                    </div>
                    <div class="flex items-center gap-1">
                        @foreach(range(1, $pagination['last_page'] ?? 1) as $page)
                            <button wire:click="goToPage({{ $page }})" class="pagination-btn {{ $page === ($pagination['current_page'] ?? 1) ? 'pagination-btn-active' : '' }}">
                                {{ $page }}
                            </button>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
    </div>

    {{-- Create/Edit Modal --}}
    @if($showModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-cloak>
            <div class="flex min-h-full items-end justify-center px-4 pb-4 pt-4 sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 transition-opacity" wire:click="closeModal()"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
                    <form wire:submit.prevent="{{ $editingId ? 'update' : 'store' }}">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">{{ $editingId ? 'Edit Sales Order' : 'New Sales Order' }}</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Customer <span class="text-red-500">*</span></label>
                                    <select wire:model="form.customer_id" class="form-input">
                                        <option value="">Select customer</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer['id'] }}">{{ $customer['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.customer_id') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Warehouse <span class="text-red-500">*</span></label>
                                    <select wire:model="form.warehouse_id" class="form-input">
                                        <option value="">Select warehouse</option>
                                        @foreach($warehouses as $warehouse)
                                            <option value="{{ $warehouse['id'] }}">{{ $warehouse['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.warehouse_id') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Order Date <span class="text-red-500">*</span></label>
                                <input type="date" wire:model="form.order_date" class="form-input">
                                @error('form.order_date') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div>
                                <div class="flex items-center justify-between mb-3">
                                    <label class="form-label mb-0">Line Items <span class="text-red-500">*</span></label>
                                    <button type="button" wire:click="addItem()" class="btn-secondary text-xs py-1.5 px-3">
                                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                                        Add Item
                                    </button>
                                </div>
                                @error('form.items') <p class="form-error mb-2">{{ $message }}</p> @enderror
                                <div class="space-y-3">
                                    @forelse($form['items'] as $index => $item)
                                        <div class="flex items-start gap-3 rounded-lg border border-slate-200 p-3 bg-slate-50">
                                            <div class="flex-1 grid grid-cols-2 gap-3 sm:grid-cols-4">
                                                <div class="col-span-2 sm:col-span-1">
                                                    <label class="text-xs font-medium text-slate-500 mb-1 block">Product</label>
                                                    <select wire:model="form.items.{{ $index }}.product_id" class="form-input text-sm">
                                                        <option value="">Select</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="text-xs font-medium text-slate-500 mb-1 block">Quantity</label>
                                                    <input type="number" wire:model="form.items.{{ $index }}.quantity" class="form-input text-sm" min="1" placeholder="1">
                                                </div>
                                                <div>
                                                    <label class="text-xs font-medium text-slate-500 mb-1 block">Unit Price</label>
                                                    <input type="number" wire:model="form.items.{{ $index }}.unit_price" class="form-input text-sm" step="0.01" min="0" placeholder="0.00">
                                                </div>
                                                <div>
                                                    <label class="text-xs font-medium text-slate-500 mb-1 block">Discount</label>
                                                    <input type="number" wire:model="form.items.{{ $index }}.discount" class="form-input text-sm" step="0.01" min="0" placeholder="0.00">
                                                </div>
                                            </div>
                                            <button type="button" wire:click="removeItem({{ $index }})" class="mt-5 action-btn action-btn-danger">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    @empty
                                        <div class="text-center py-4 text-sm text-slate-400 border border-dashed border-slate-200 rounded-lg">
                                            No items added. Click "Add Item" to begin.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closeModal()" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="store, update">{{ $editingId ? 'Update Order' : 'Create Order' }}</span>
                                <span wire:loading wire:target="store, update" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Saving...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Fulfill Modal --}}
    @if($showFulfillModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-cloak>
            <div class="flex min-h-full items-end justify-center px-4 pb-4 pt-4 sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 transition-opacity" wire:click="closeFulfillModal()"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form wire:submit.prevent="submitFulfill">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Fulfill Order</h3>
                        </div>
                        <div class="px-6 py-4 space-y-3 max-h-[60vh] overflow-y-auto">
                            @forelse($fulfillItems as $index => $fItem)
                                <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-slate-900">Item #{{ $fItem['sales_order_item_id'] }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs text-slate-500">Qty to Ship</label>
                                        <input type="number" wire:model="fulfillItems.{{ $index }}.quantity" class="form-input text-sm w-24" min="1">
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-slate-500 text-center py-4">No items to fulfill.</p>
                            @endforelse
                            <div>
                                <label class="form-label">Note</label>
                                <textarea wire:model="fulfillNote" class="form-input" rows="2" placeholder="Optional note..."></textarea>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closeFulfillModal()" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitFulfill">Confirm Shipment</span>
                                <span wire:loading wire:target="submitFulfill" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Pay Modal --}}
    @if($showPayModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-cloak>
            <div class="flex min-h-full items-end justify-center px-4 pb-4 pt-4 sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 transition-opacity" wire:click="closePayModal()"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <form wire:submit.prevent="submitPay">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Record Payment</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4">
                            <div>
                                <label class="form-label">Amount <span class="text-red-500">*</span></label>
                                <input type="number" wire:model="payAmount" class="form-input" step="0.01" min="0.01" placeholder="0.00" required>
                            </div>
                            <div>
                                <label class="form-label">Method <span class="text-red-500">*</span></label>
                                <select wire:model="payMethod" class="form-input">
                                    <option value="cash">Cash</option>
                                    <option value="card">Card</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="other">Other</option>
                                </select>
                            </div>
                            <div>
                                <label class="form-label">Reference</label>
                                <input type="text" wire:model="payReference" class="form-input" placeholder="Invoice #, receipt #, etc.">
                            </div>
                            <div>
                                <label class="form-label">Note</label>
                                <textarea wire:model="payNote" class="form-input" rows="2" placeholder="Optional note..."></textarea>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closePayModal()" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-success" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitPay">Record Payment</span>
                                <span wire:loading wire:target="submitPay" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- Refund Modal --}}
    @if($showRefundModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-cloak>
            <div class="flex min-h-full items-end justify-center px-4 pb-4 pt-4 sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-slate-900/50 transition-opacity" wire:click="closeRefundModal()"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form wire:submit.prevent="submitRefund">
                        <div class="border-b border-slate-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-slate-900">Process Refund</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4 max-h-[60vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Refund Amount <span class="text-red-500">*</span></label>
                                    <input type="number" wire:model="refundAmount" class="form-input" step="0.01" min="0.01" placeholder="0.00" required>
                                </div>
                                <div>
                                    <label class="form-label">Method <span class="text-red-500">*</span></label>
                                    <select wire:model="refundMethod" class="form-input">
                                        <option value="cash">Cash</option>
                                        <option value="card">Card</option>
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="other">Other</option>
                                    </select>
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Reference</label>
                                <input type="text" wire:model="refundReference" class="form-input" placeholder="Reference number">
                            </div>
                            @if(count($refundItems) > 0)
                                <div>
                                    <label class="form-label">Return Items</label>
                                    @foreach($refundItems as $index => $rItem)
                                        <div class="flex items-center gap-3 rounded-lg border border-slate-200 p-2 mt-2">
                                            <span class="text-sm text-slate-700 flex-1">Item #{{ $rItem['sales_order_item_id'] }}</span>
                                            <input type="number" wire:model="refundItems.{{ $index }}.quantity" class="form-input text-sm w-20" min="1">
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                            <div>
                                <label class="form-label">Note</label>
                                <textarea wire:model="refundNote" class="form-input" rows="2" placeholder="Optional note..."></textarea>
                            </div>
                        </div>
                        <div class="border-t border-slate-100 bg-slate-50/80 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closeRefundModal()" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-warning" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitRefund">Process Refund</span>
                                <span wire:loading wire:target="submitRefund" class="flex items-center gap-2">
                                    <svg class="h-4 w-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                                    Processing...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif
@endif
</div>
