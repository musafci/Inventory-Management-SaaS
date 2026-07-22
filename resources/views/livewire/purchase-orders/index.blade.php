<div x-data="{ toasts: [], showReceiveModal: @entangle('showReceiveModal'), showPayModal: @entangle('showPayModal') }"
     x-on:toast.window="toasts.push({msg: $event.detail.message, type: $event.detail.type || 'success', id: Date.now()}); setTimeout(() => toasts.shift(), 4000)">
@if($detail)
    @include('livewire.purchase-orders.show-detail')
@else
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1 max-w-lg">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" class="form-input pl-10" placeholder="Search purchase orders..." wire:loading.class="search-loading" wire:target="search">
            </div>
        </div>
        <button wire:click="openModal()" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            New Purchase Order
        </button>
    </div>

    <div class="card overflow-hidden" wire:loading.class="wire-loading-dim" wire:target="items">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">PO Number</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Supplier</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Order Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white" wire:transition.opacity.duration.300ms>
                    @forelse($items as $item)
                        <tr class="table-row-hover">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <a href="/purchase-orders/{{ $item['id'] }}" class="text-primary-600 hover:text-primary-500">{{ $item['po_number'] ?? '-' }}</a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['supplier']['name'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @php
                                    $statusColors = [
                                        'draft' => 'bg-gray-100 text-gray-700',
                                        'sent' => 'bg-blue-100 text-blue-700',
                                        'partially_received' => 'bg-amber-100 text-amber-700',
                                        'received' => 'bg-emerald-100 text-emerald-700',
                                        'cancelled' => 'bg-red-100 text-red-700',
                                    ];
                                    $status = $item['status'] ?? 'draft';
                                @endphp
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ str_replace('_', ' ', ucfirst($status)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['order_date'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">${{ number_format($item['total_amount'] ?? 0, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-1">
                                    @if(($item['status'] ?? '') === 'draft')
                                        <button wire:click="editItem({{ $item['id'] }})" class="text-gray-400 hover:text-primary-600 transition-colors" title="Edit">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125" /></svg>
                                        </button>
                                        <button @click.prevent="$store.confirm.open('Send Purchase Order', 'Send this purchase order to the supplier?', 'warning', () => $wire.send({{ $item['id'] }}), 'Send')" class="text-gray-400 hover:text-blue-600 transition-colors" title="Send">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" /></svg>
                                        </button>
                                    @endif
                                    @if(in_array(($item['status'] ?? ''), ['sent', 'partially_received']))
                                        <button wire:click="openReceiveModal({{ $item['id'] }})" class="text-gray-400 hover:text-emerald-600 transition-colors" title="Receive Goods">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" /></svg>
                                        </button>
                                    @endif
                                    @if(in_array(($item['status'] ?? ''), ['sent', 'partially_received', 'received']))
                                        <button wire:click="openPayModal({{ $item['id'] }})" class="text-gray-400 hover:text-amber-600 transition-colors" title="Record Payment">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                                        </button>
                                    @endif
                                    @if(in_array(($item['status'] ?? ''), ['draft', 'sent']))
                                        <button @click.prevent="$store.confirm.open('Cancel Order', 'Cancel this purchase order?', 'danger', () => $wire.cancel({{ $item['id'] }}), 'Cancel Order')" class="text-gray-400 hover:text-red-600 transition-colors" title="Cancel">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                        </button>
                                    @endif
                                    @if(($item['status'] ?? '') === 'draft')
                                        <button @click.prevent="$store.confirm.open('Delete Order', 'Delete this purchase order? This action cannot be undone.', 'danger', () => $wire.destroy({{ $item['id'] }}), 'Delete')" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                        </button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                                <p class="mt-2 text-sm text-gray-500">No purchase orders found.</p>
                                <button wire:click="openModal()" class="mt-3 btn-primary text-sm">New Purchase Order</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!empty($pagination['last_page']) && $pagination['last_page'] > 1)
            <div class="border-t border-gray-200 bg-white px-4 py-3 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-gray-700">
                        Showing {{ ($pagination['current_page'] - 1) * $perPage + 1 }} to {{ min($pagination['current_page'] * $perPage, $pagination['total'] ?? 0) }} of {{ $pagination['total'] ?? 0 }} results
                    </div>
                    <div class="flex items-center gap-1">
                        @foreach(range(1, $pagination['last_page'] ?? 1) as $page)
                            <button wire:click="goToPage({{ $page }})" class="inline-flex items-center justify-center rounded-lg px-3 py-1.5 text-sm font-medium transition-colors {{ $page === ($pagination['current_page'] ?? 1) ? 'bg-primary-600 text-white' : 'text-gray-700 hover:bg-gray-100' }}">
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
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closeModal()"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-3xl">
                    <form wire:submit.prevent="{{ $editingId ? 'update' : 'store' }}">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Purchase Order' : 'New Purchase Order' }}</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Supplier <span class="text-red-500">*</span></label>
                                    <select wire:model="form.supplier_id" class="form-input">
                                        <option value="">Select supplier</option>
                                        @foreach($suppliers as $supplier)
                                            <option value="{{ $supplier['id'] }}">{{ $supplier['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.supplier_id') <p class="form-error">{{ $message }}</p> @enderror
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
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Order Date <span class="text-red-500">*</span></label>
                                    <input type="date" wire:model="form.order_date" class="form-input">
                                    @error('form.order_date') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Expected Date</label>
                                    <input type="date" wire:model="form.expected_date" class="form-input">
                                </div>
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
                                        <div class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 bg-gray-50">
                                            <div class="flex-1 grid grid-cols-3 gap-3">
                                                <div>
                                                    <label class="text-xs font-medium text-gray-500 mb-1 block">Product</label>
                                                    <select wire:model="form.items.{{ $index }}.product_id" class="form-input text-sm">
                                                        <option value="">Select</option>
                                                        @foreach($products as $product)
                                                            <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                                                        @endforeach
                                                    </select>
                                                </div>
                                                <div>
                                                    <label class="text-xs font-medium text-gray-500 mb-1 block">Quantity</label>
                                                    <input type="number" wire:model="form.items.{{ $index }}.quantity_ordered" class="form-input text-sm" min="1" placeholder="1">
                                                </div>
                                                <div>
                                                    <label class="text-xs font-medium text-gray-500 mb-1 block">Unit Cost</label>
                                                    <input type="number" wire:model="form.items.{{ $index }}.unit_cost" class="form-input text-sm" step="0.01" min="0" placeholder="0.00">
                                                </div>
                                            </div>
                                            <button type="button" wire:click="removeItem({{ $index }})" class="mt-5 text-gray-400 hover:text-red-600">
                                                <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" /></svg>
                                            </button>
                                        </div>
                                    @empty
                                        <div class="text-center py-4 text-sm text-gray-400 border border-dashed border-gray-300 rounded-lg">
                                            No items added. Click "Add Item" to begin.
                                        </div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex justify-end gap-3">
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

    {{-- Receive Goods Modal --}}
    @if($showReceiveModal)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data x-cloak>
            <div class="flex min-h-full items-end justify-center px-4 pb-4 pt-4 sm:items-center sm:p-0">
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closeReceiveModal()"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg">
                    <form wire:submit.prevent="submitReceive">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">Receive Goods</h3>
                        </div>
                        <div class="px-6 py-4 space-y-3 max-h-[60vh] overflow-y-auto">
                            @forelse($receiveItems as $index => $rItem)
                                <div class="flex items-center gap-3 rounded-lg border border-gray-200 p-3">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">Item #{{ $rItem['purchase_order_item_id'] }}</p>
                                    </div>
                                    <div>
                                        <label class="text-xs text-gray-500">Qty to Receive</label>
                                        <input type="number" wire:model="receiveItems.{{ $index }}.quantity" class="form-input text-sm w-24" min="1">
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 text-center py-4">No items to receive.</p>
                            @endforelse
                            <div>
                                <label class="form-label">Note</label>
                                <textarea wire:model="receiveNote" class="form-input" rows="2" placeholder="Optional note..."></textarea>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closeReceiveModal()" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-success" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitReceive">Confirm Receipt</span>
                                <span wire:loading wire:target="submitReceive" class="flex items-center gap-2">
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
                <div class="fixed inset-0 bg-gray-900/50 transition-opacity" wire:click="closePayModal()"></div>
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-md">
                    <form wire:submit.prevent="submitPay">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">Record Payment</h3>
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
                                <input type="text" wire:model="payReference" class="form-input" placeholder="Invoice #, check #, etc.">
                            </div>
                            <div>
                                <label class="form-label">Note</label>
                                <textarea wire:model="payNote" class="form-input" rows="2" placeholder="Optional note..."></textarea>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex justify-end gap-3">
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
@endif
</div>
