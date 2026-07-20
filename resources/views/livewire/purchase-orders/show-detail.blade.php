@php
    $status = $detail['status'] ?? 'draft';
    $statusLabels = [
        'draft' => ['badge-warning', 'Draft'],
        'sent' => ['badge-info', 'Sent'],
        'partially_received' => ['badge-warning', 'Partially received'],
        'received' => ['badge-success', 'Received'],
        'cancelled' => ['badge-danger', 'Cancelled'],
    ];
    [$badgeClass, $statusLabel] = $statusLabels[$status] ?? ['badge-neutral', ucfirst(str_replace('_', ' ', $status))];
@endphp

<x-detail-page :title="$detail['po_number'] ?? 'Purchase order'" back-url="/purchase-orders" back-label="Back to purchase orders">
    <div class="mb-6 flex flex-wrap items-center gap-3">
        <span class="badge {{ $badgeClass }}">{{ $statusLabel }}</span>
        <span class="text-sm text-slate-500">Order date: {{ $detail['order_date'] ?? '—' }}</span>
        @if(!empty($detail['expected_date']))
            <span class="text-sm text-slate-500">Expected: {{ $detail['expected_date'] }}</span>
        @endif
    </div>

    <dl class="mb-8 grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Supplier</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ $detail['supplier']['name'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Warehouse</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ $detail['warehouse']['name'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Total amount</dt>
            <dd class="mt-1 text-lg font-bold text-slate-900">${{ number_format($detail['total_amount'] ?? 0, 2) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Amount due</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">${{ number_format($detail['amount_due'] ?? 0, 2) }}</dd>
        </div>
    </dl>

    @if(!empty($detail['items']))
        <h3 class="mb-3 text-sm font-semibold text-slate-900">Line items</h3>
        <div class="overflow-hidden rounded-xl ring-1 ring-slate-200">
            <table class="min-w-full divide-y divide-slate-200">
                <thead class="bg-slate-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Product</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Qty ordered</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Received</th>
                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-slate-500">Unit cost</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 bg-white">
                    @foreach($detail['items'] as $item)
                        <tr>
                            <td class="px-4 py-3 text-sm text-slate-900">{{ $item['product']['name'] ?? 'Product #'.$item['product_id'] }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item['quantity_ordered'] ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-slate-600">{{ $item['quantity_received'] ?? 0 }}</td>
                            <td class="px-4 py-3 text-sm text-slate-900">${{ number_format($item['unit_cost'] ?? 0, 2) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @endif

    <div class="mt-8 flex gap-3 border-t border-slate-100 pt-6">
        @if($status === 'draft')
            <a href="/purchase-orders/{{ $detail['id'] }}/edit" class="btn-primary">Edit order</a>
        @endif
    </div>
</x-detail-page>
