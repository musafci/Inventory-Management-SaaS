<div>
@if($detail)
    @include('livewire.products.show-detail')
@else
    {{-- Header --}}
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex-1 max-w-lg">
            <div class="relative">
                <div class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3">
                    <svg class="h-5 w-5 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                </div>
                <input type="text" wire:model.live.debounce.300ms="search" class="search-input" placeholder="Search products...">
            </div>
        </div>
        <button wire:click="openModal()" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            Add Product
        </button>
    </div>

    {{-- Table --}}
    <div class="card overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th wire:click="sortBy('name')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700">
                            <div class="flex items-center gap-1">Name @if($sortField === 'name')<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" /></svg>@endif</div>
                        </th>
                        <th wire:click="sortBy('sku')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700">
                            <div class="flex items-center gap-1">SKU @if($sortField === 'sku')<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" /></svg>@endif</div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Category</th>
                        <th wire:click="sortBy('cost_price')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700">
                            <div class="flex items-center gap-1">Cost Price @if($sortField === 'cost_price')<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" /></svg>@endif</div>
                        </th>
                        <th wire:click="sortBy('selling_price')" class="cursor-pointer px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 hover:text-gray-700">
                            <div class="flex items-center gap-1">Selling Price @if($sortField === 'selling_price')<svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="{{ $sortDirection === 'asc' ? 'M4.5 15.75l7.5-7.5 7.5 7.5' : 'M19.5 8.25l-7.5 7.5-7.5-7.5' }}" /></svg>@endif</div>
                        </th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white">
                    @forelse($items as $item)
                        <tr class="table-row-hover">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">
                                <a href="/products/{{ $item['id'] }}" class="text-primary-600 hover:text-primary-500">{{ $item['name'] }}</a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['sku'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['category']['name'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ number_format($item['cost_price'] ?? 0, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ number_format($item['selling_price'] ?? 0, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($item['is_active'] ?? true)
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">Active</span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-medium text-gray-600">Inactive</span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-right text-sm">
                                <div class="flex items-center justify-end gap-2" x-data="{ open: false }">
                                    <button wire:click="editItem({{ $item['id'] }})" class="text-gray-400 hover:text-primary-600 transition-colors" title="Edit">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0115.75 21H5.25A2.25 2.25 0 013 18.75V8.25A2.25 2.25 0 015.25 6H10" /></svg>
                                    </button>
                                    <button @click="$store.confirm.open('Delete Product', 'Are you sure you want to delete this product? This action cannot be undone.', 'danger', () => $wire.destroy({{ $item['id'] }}), 'Delete')" class="text-gray-400 hover:text-red-600 transition-colors" title="Delete">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
                                <p class="mt-2 text-sm text-gray-500">No products found.</p>
                                <button wire:click="openModal()" class="mt-3 btn-primary text-sm">Add Product</button>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
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
                <div class="relative transform overflow-hidden rounded-2xl bg-white text-left shadow-xl transition-all sm:my-8 sm:w-full sm:max-w-lg"
                     x-transition:enter="transition ease-out duration-200"
                     x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
                     x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave="transition ease-in duration-100"
                     x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
                     x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95">
                    <form wire:submit.prevent="{{ $editingId ? 'update' : 'store' }}">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">{{ $editingId ? 'Edit Product' : 'Add Product' }}</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4 max-h-[70vh] overflow-y-auto">
                            <div>
                                <label class="form-label">Name <span class="text-red-500">*</span></label>
                                <input type="text" wire:model="form.name" class="form-input" placeholder="Product name">
                                @error('form.name') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">SKU</label>
                                    <input type="text" wire:model="form.sku" class="form-input" placeholder="SKU">
                                    @error('form.sku') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Barcode</label>
                                    <input type="text" wire:model="form.barcode" class="form-input" placeholder="Barcode">
                                    @error('form.barcode') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Category <span class="text-red-500">*</span></label>
                                    <select wire:model="form.category_id" class="form-input">
                                        <option value="">Select category</option>
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat['id'] }}">{{ $cat['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.category_id') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Unit <span class="text-red-500">*</span></label>
                                    <select wire:model="form.unit_id" class="form-input">
                                        <option value="">Select unit</option>
                                        @foreach($units as $unit)
                                            <option value="{{ $unit['id'] }}">{{ $unit['name'] }} ({{ $unit['symbol'] }})</option>
                                        @endforeach
                                    </select>
                                    @error('form.unit_id') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Cost Price <span class="text-red-500">*</span></label>
                                    <input type="number" wire:model="form.cost_price" class="form-input" step="0.01" min="0" placeholder="0.00">
                                    @error('form.cost_price') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Selling Price <span class="text-red-500">*</span></label>
                                    <input type="number" wire:model="form.selling_price" class="form-input" step="0.01" min="0" placeholder="0.00">
                                    @error('form.selling_price') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Tax Rate (%)</label>
                                    <input type="number" wire:model="form.tax_rate" class="form-input" step="0.01" min="0" max="100" placeholder="0">
                                    @error('form.tax_rate') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Reorder Point</label>
                                    <input type="number" wire:model="form.reorder_point" class="form-input" min="0" placeholder="0">
                                    @error('form.reorder_point') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <input type="checkbox" wire:model="form.is_active" id="is_active" class="h-4 w-4 rounded border-gray-300 text-primary-600 focus:ring-primary-500">
                                <label for="is_active" class="text-sm font-medium text-gray-700">Active</label>
                            </div>
                        </div>
                        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closeModal()" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="store, update">{{ $editingId ? 'Update' : 'Create' }}</span>
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
@endif
</div>
