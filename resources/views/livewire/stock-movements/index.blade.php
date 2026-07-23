<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-list-search wire:model.live.debounce.300ms="search" placeholder="Search stock movements..." />
        <button wire:click="openModal()" class="btn-primary">
            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
            New Adjustment
        </button>
    </div>

    <div class="card overflow-hidden" wire:loading.class="wire-loading-dim" wire:target="items">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Warehouse</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Type</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Quantity</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Note</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white" wire:transition.opacity.duration.300ms>
                    @forelse($items as $item)
                        @php
                            $typeColors = [
                                'purchase_in' => 'bg-emerald-100 text-emerald-700',
                                'sale_out' => 'bg-red-100 text-red-700',
                                'adjustment_in' => 'bg-blue-100 text-blue-700',
                                'adjustment_out' => 'bg-orange-100 text-orange-700',
                                'transfer_in' => 'bg-indigo-100 text-indigo-700',
                                'transfer_out' => 'bg-purple-100 text-purple-700',
                            ];
                            $type = $item['type'] ?? '';
                        @endphp
                        <tr class="table-row-hover">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['created_at'] ?? $item['date'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $item['product']['name'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['warehouse']['name'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $typeColors[$type] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ str_replace('_', ' ', ucfirst($type)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium {{ in_array($type, ['sale_out', 'adjustment_out', 'transfer_out']) ? 'text-red-600' : 'text-emerald-600' }}">
                                {{ in_array($type, ['sale_out', 'adjustment_out', 'transfer_out']) ? '-' : '+' }}{{ $item['quantity'] ?? 0 }}
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['reference'] ?? '-' }}</td>
                            <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">{{ $item['note'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 21L3 16.5m0 0L7.5 12M3 16.5h13.5m0-13.5L21 7.5m0 0L16.5 12M21 7.5H7.5" /></svg>
                                <p class="mt-2 text-sm text-gray-500">No stock movements found.</p>
                                <button wire:click="openModal()" class="mt-3 btn-primary text-sm">New Adjustment</button>
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

    {{-- New Adjustment Modal --}}
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
                    <form wire:submit.prevent="store">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h3 class="text-lg font-semibold text-gray-900">New Stock Adjustment</h3>
                        </div>
                        <div class="px-6 py-4 space-y-4">
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="form-label">Product <span class="text-red-500">*</span></label>
                                    <select wire:model="form.product_id" class="form-input">
                                        <option value="">Select product</option>
                                        @foreach($products as $product)
                                            <option value="{{ $product['id'] }}">{{ $product['name'] }}</option>
                                        @endforeach
                                    </select>
                                    @error('form.product_id') <p class="form-error">{{ $message }}</p> @enderror
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
                                    <label class="form-label">Type <span class="text-red-500">*</span></label>
                                    <select wire:model="form.type" class="form-input">
                                        <option value="adjustment_in">Adjustment In</option>
                                        <option value="adjustment_out">Adjustment Out</option>
                                        <option value="transfer_in">Transfer In</option>
                                        <option value="transfer_out">Transfer Out</option>
                                    </select>
                                    @error('form.type') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                                <div>
                                    <label class="form-label">Quantity <span class="text-red-500">*</span></label>
                                    <input type="number" wire:model="form.quantity" class="form-input" min="1" placeholder="1">
                                    @error('form.quantity') <p class="form-error">{{ $message }}</p> @enderror
                                </div>
                            </div>
                            <div>
                                <label class="form-label">Note</label>
                                <textarea wire:model="form.note" class="form-input" rows="3" placeholder="Optional note for this adjustment"></textarea>
                                @error('form.note') <p class="form-error">{{ $message }}</p> @enderror
                            </div>
                        </div>
                        <div class="border-t border-gray-100 bg-gray-50 px-6 py-4 flex justify-end gap-3">
                            <button type="button" wire:click="closeModal()" class="btn-secondary">Cancel</button>
                            <button type="submit" class="btn-primary" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="store">Record Adjustment</span>
                                <span wire:loading wire:target="store" class="flex items-center gap-2">
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
</div>
