<div>
@if($detail)
    @include('livewire.payments.show-detail')
@else
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-list-search wire:model.live.debounce.300ms="search" placeholder="Search payments..." />
    </div>

    <div class="card overflow-hidden" wire:loading.class="wire-loading-dim" wire:target="items">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 bg-white" wire:transition.opacity.duration.300ms>
                    @forelse($items as $item)
                        @php
                            $methodColors = [
                                'cash' => 'bg-emerald-100 text-emerald-700',
                                'bank_transfer' => 'bg-primary-100 text-primary-700',
                                'credit_card' => 'bg-purple-100 text-purple-700',
                                'debit_card' => 'bg-primary-100 text-primary-700',
                                'check' => 'bg-amber-100 text-amber-700',
                                'other' => 'bg-gray-100 text-gray-700',
                            ];
                            $statusColors = [
                                'completed' => 'bg-emerald-100 text-emerald-700',
                                'pending' => 'bg-amber-100 text-amber-700',
                                'failed' => 'bg-red-100 text-red-700',
                                'refunded' => 'bg-orange-100 text-orange-700',
                            ];
                            $method = $item['payment_method'] ?? $item['method'] ?? 'other';
                            $status = $item['status'] ?? 'completed';
                        @endphp
                        <tr class="table-row-hover">
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">
                                @if(!empty($item['paid_at']))
                                    {{ \Illuminate\Support\Carbon::parse($item['paid_at'])->format('M j, Y g:i A') }}
                                @elseif(!empty($item['created_at']))
                                    {{ \Illuminate\Support\Carbon::parse($item['created_at'])->format('M j, Y g:i A') }}
                                @else
                                    —
                                @endif
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium">
                                <a href="/payments/{{ $item['id'] }}" class="text-primary-600 hover:text-primary-500">{{ $item['order']['order_number'] ?? $item['order_number'] ?? 'Payment #'.$item['id'] }}</a>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">${{ number_format($item['amount'] ?? 0, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $methodColors[$method] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ str_replace('_', ' ', ucfirst($method)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$status] ?? 'bg-gray-100 text-gray-700' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['reference_number'] ?? $item['reference'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['recorded_by']['name'] ?? $item['user']['name'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center">
                                <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                                <p class="mt-2 text-sm text-gray-500">No payments found.</p>
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
@endif
</div>
