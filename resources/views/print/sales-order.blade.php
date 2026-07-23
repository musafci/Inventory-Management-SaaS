@extends('layouts.print')

@section('title', ($order['order_number'] ?? 'Sales Order').' — Print')

@section('toolbar-label', 'Sales order '.$order['order_number'])

@section('content')
    <header class="print-header">
        <div>
            <p class="print-org">{{ $organization['name'] ?? 'Organization' }}</p>
            <h1 class="print-doc-title">Sales Order</h1>
        </div>
        <div class="print-meta">
            <strong>{{ $order['order_number'] ?? '—' }}</strong>
            <div>Date: {{ $order['order_date'] ?? '—' }}</div>
            <div>Status: {{ ucfirst($order['status'] ?? 'draft') }}</div>
        </div>
    </header>

    <div class="print-grid">
        <div>
            <h2 class="print-section-title">Bill to</h2>
            <div class="print-section-body">
                <p><strong>{{ $order['customer']['name'] ?? '—' }}</strong></p>
                @if(!empty($order['customer']['email']))
                    <p>{{ $order['customer']['email'] }}</p>
                @endif
                @if(!empty($order['customer']['phone']))
                    <p>{{ $order['customer']['phone'] }}</p>
                @endif
                @if(!empty($order['customer']['address']))
                    <p>{{ $order['customer']['address'] }}</p>
                @endif
            </div>
        </div>
        <div>
            <h2 class="print-section-title">Ship from</h2>
            <div class="print-section-body">
                <p><strong>{{ $order['warehouse']['name'] ?? '—' }}</strong></p>
                @if(!empty($order['warehouse']['address']))
                    <p>{{ $order['warehouse']['address'] }}</p>
                @endif
            </div>
        </div>
    </div>

    <table class="print-table">
        <thead>
            <tr>
                <th>Product</th>
                <th>SKU</th>
                <th class="text-right">Qty</th>
                <th class="text-right">Unit price</th>
                <th class="text-right">Discount</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order['items'] ?? [] as $item)
                <tr>
                    <td>{{ $item['product']['name'] ?? 'Product #'.($item['product_id'] ?? '') }}</td>
                    <td>{{ $item['product']['sku'] ?? '—' }}</td>
                    <td class="text-right">{{ $item['quantity'] ?? 0 }}</td>
                    <td class="text-right">${{ number_format((float) ($item['unit_price'] ?? 0), 2) }}</td>
                    <td class="text-right">${{ number_format((float) ($item['discount'] ?? 0), 2) }}</td>
                    <td class="text-right">${{ number_format((float) ($item['subtotal'] ?? 0), 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="print-total">
        <div class="print-total-row grand">
            <span>Total</span>
            <span>${{ number_format((float) ($order['total_amount'] ?? 0), 2) }}</span>
        </div>
        @if(isset($order['amount_due']))
            <div class="print-total-row">
                <span>Amount due</span>
                <span>${{ number_format((float) $order['amount_due'], 2) }}</span>
            </div>
        @endif
    </div>

    <footer class="print-footer">
        Generated {{ now()->format('M j, Y g:i A') }}
    </footer>
@endsection
