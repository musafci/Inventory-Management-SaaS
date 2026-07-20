@php
    $method = $detail['method'] ?? 'other';
    $status = $detail['status'] ?? 'completed';
    $payable = $detail['payable'] ?? [];
    $orderNumber = $payable['po_number'] ?? $payable['order_number'] ?? ('#' . ($detail['payable_id'] ?? ''));
    $orderType = str_contains($detail['payable_type'] ?? '', 'PurchaseOrder') ? 'Purchase order' : 'Sales order';
    $orderUrl = str_contains($detail['payable_type'] ?? '', 'PurchaseOrder')
        ? '/purchase-orders/' . ($detail['payable_id'] ?? '')
        : '/sales-orders/' . ($detail['payable_id'] ?? '');
@endphp

<x-detail-page title="Payment #{{ $detail['id'] }}" back-url="/payments" back-label="Back to payments">
    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</dt>
            <dd class="mt-1 text-2xl font-bold text-slate-900">${{ number_format($detail['amount'] ?? 0, 2) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</dt>
            <dd class="mt-1"><span class="badge badge-success">{{ ucfirst($status) }}</span></dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Method</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ str_replace('_', ' ', ucfirst($method)) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Paid at</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ isset($detail['paid_at']) ? \Illuminate\Support\Carbon::parse($detail['paid_at'])->format('M j, Y g:i A') : '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ $orderType }}</dt>
            <dd class="mt-1">
                <a href="{{ $orderUrl }}" class="text-sm font-semibold text-primary-600 hover:text-primary-500">{{ $orderNumber }}</a>
            </dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $detail['reference'] ?? '—' }}</dd>
        </div>
        <div class="sm:col-span-2">
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Note</dt>
            <dd class="mt-1 text-sm text-slate-700">{{ $detail['note'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Recorded by</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $detail['recorded_by_user']['name'] ?? '—' }}</dd>
        </div>
    </dl>
</x-detail-page>
