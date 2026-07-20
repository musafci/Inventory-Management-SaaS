@php
    $categoryName = collect($categories)->firstWhere('id', $detail['category_id'])['name'] ?? '-';
    $unit = collect($units)->firstWhere('id', $detail['unit_id']);
    $unitLabel = $unit ? ($unit['name'] . ' (' . ($unit['symbol'] ?? '') . ')') : '-';
@endphp

<x-detail-page :title="$detail['name']" back-url="/products" back-label="Back to products">
    <dl class="grid grid-cols-1 gap-6 sm:grid-cols-2">
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">SKU</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ $detail['sku'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Barcode</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">{{ $detail['barcode'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Category</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $categoryName }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Unit</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $unitLabel }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Cost price</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">${{ number_format($detail['cost_price'] ?? 0, 2) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Selling price</dt>
            <dd class="mt-1 text-sm font-semibold text-slate-900">${{ number_format($detail['selling_price'] ?? 0, 2) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Tax rate</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $detail['tax_rate'] ?? 0 }}%</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Reorder point</dt>
            <dd class="mt-1 text-sm text-slate-900">{{ $detail['reorder_point'] ?? '—' }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Status</dt>
            <dd class="mt-1">
                @if($detail['is_active'] ?? true)
                    <span class="badge badge-success">Active</span>
                @else
                    <span class="badge badge-neutral">Inactive</span>
                @endif
            </dd>
        </div>
    </dl>

    <div class="mt-8 flex gap-3 border-t border-slate-100 pt-6">
        <a href="/products/{{ $detail['id'] }}/edit" class="btn-primary">Edit product</a>
    </div>
</x-detail-page>
