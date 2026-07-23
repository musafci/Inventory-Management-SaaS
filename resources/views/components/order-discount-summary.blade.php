@props([
    'order',
    'compact' => false,
])

@php
    $grossSubtotal = (float) ($order['gross_subtotal'] ?? 0);
    $totalDiscount = (float) ($order['total_discount'] ?? 0);
    $totalAmount = (float) ($order['total_amount'] ?? 0);
    $hasDiscount = $totalDiscount > 0;
@endphp

@if($hasDiscount)
    @if($compact)
        <div class="print-total-row">
            <span>Subtotal</span>
            <span>${{ number_format($grossSubtotal, 2) }}</span>
        </div>
        <div class="print-total-row">
            <span>Discount</span>
            <span>-${{ number_format($totalDiscount, 2) }}</span>
        </div>
    @else
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Subtotal</dt>
            <dd class="mt-1 text-sm font-medium text-slate-900">${{ number_format($grossSubtotal, 2) }}</dd>
        </div>
        <div>
            <dt class="text-xs font-semibold uppercase tracking-wider text-slate-500">Discount</dt>
            <dd class="mt-1 text-sm font-semibold text-emerald-700">-${{ number_format($totalDiscount, 2) }}</dd>
        </div>
    @endif
@endif

@if($compact)
    <div class="print-total-row grand">
        <span>Total</span>
        <span>${{ number_format($totalAmount, 2) }}</span>
    </div>
@endif
