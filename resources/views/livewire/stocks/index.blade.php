<div>
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-list-search wire:model.live.debounce.300ms="search" placeholder="Search stocks..." />
    </div>

    <div class="card overflow-hidden" wire:loading.class="wire-loading-dim" wire:target="items">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Warehouse</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">On Hand</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reserved</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Available</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Last Counted</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white" wire:transition.opacity.duration.300ms>
                    @forelse($items as $item)
                        @php
                            $available = $item['quantity_available'] ?? ($item['on_hand'] ?? 0) - ($item['reserved'] ?? 0);
                            $reorderPoint = $item['product']['reorder_point'] ?? 0;
                            $isLowStock = $available <= $reorderPoint && $reorderPoint > 0;
                            $isOutOfStock = $available <= 0;
                        @endphp
                        <tr class="table-row-hover">
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $item['product']['name'] ?? 'Unknown Product' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['warehouse']['name'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="text-sm {{ $isOutOfStock ? 'font-semibold text-red-600' : 'text-gray-900' }}">
                                    {{ $item['quantity_on_hand'] ?? $item['on_hand'] ?? 0 }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['reserved'] ?? 0 }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                @if($isOutOfStock)
                                    <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">
                                        {{ $available }} Out of Stock
                                    </span>
                                @elseif($isLowStock)
                                    <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">
                                        {{ $available }} Low
                                    </span>
                                @else
                                    <span class="inline-flex items-center rounded-full bg-emerald-100 px-2.5 py-0.5 text-xs font-medium text-emerald-800">
                                        {{ $available }} In Stock
                                    </span>
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['last_counted_at'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V7.5" /></svg>
                                <p class="mt-2 text-sm text-gray-500">No stock records found.</p>
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
</div>
