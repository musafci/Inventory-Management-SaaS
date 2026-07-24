<div>
@if($detail)
    @include('livewire.payments.show-detail')
@else
    <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <x-list-search wire:model.live.debounce.300ms="search" placeholder="Search payments..." />
    </div>

    <div class="card overflow-hidden" wire:loading.class="wire-loading-dim" wire:target="items">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-slate-100">
                <thead class="bg-slate-50/80">
                    <tr>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Date</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Order</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Amount</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Method</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Status</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Reference</th>
                        <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-slate-500">Recorded By</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white" wire:transition.opacity.duration.300ms>
                    @forelse($items as $item)
                        @php
                            $methodColors = [
                                'cash' => 'bg-emerald-100 text-emerald-700',
                                'bank_transfer' => 'bg-primary-100 text-primary-700',
                                'credit_card' => 'bg-purple-100 text-purple-700',
                                'debit_card' => 'bg-primary-100 text-primary-700',
                                'check' => 'bg-amber-100 text-amber-700',
                                'other' => 'bg-slate-100 text-slate-700',
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
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">
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
                            <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-slate-900">${{ number_format($item['amount'] ?? 0, 2) }}</td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $methodColors[$method] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ str_replace('_', ' ', ucfirst($method)) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium {{ $statusColors[$status] ?? 'bg-slate-100 text-slate-700' }}">
                                    {{ ucfirst($status) }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $item['reference_number'] ?? $item['reference'] ?? '-' }}</td>
                            <td class="whitespace-nowrap px-6 py-4 text-sm text-slate-500">{{ $item['recorded_by']['name'] ?? $item['user']['name'] ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-16 text-center">
                                <div class="empty-state-icon emerald">
                                    <svg class="h-8 w-8" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818l.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                </div>
                                <p class="empty-state-title">No payments yet</p>
                                <p class="empty-state-desc">Payments are recorded when processing sales orders and purchase orders.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if(!empty($pagination['last_page']) && $pagination['last_page'] > 1)
            <div class="border-t border-slate-200 bg-white px-4 py-3 sm:px-6">
                <div class="flex items-center justify-between">
                    <div class="text-sm text-slate-700">
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
@endif
</div>
