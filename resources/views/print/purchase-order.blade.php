@extends('layouts.print')

@section('title', ($order['po_number'] ?? 'Purchase Order').' — Print')

@section('toolbar-label', 'Purchase order '.$order['po_number'])

@section('content')
    <header class="print-header">
        <div>
            <p class="print-org">{{ $organization['name'] ?? 'Organization' }}</p>
            <h1 class="print-doc-title">Purchase Order</h1>
        </div>
        <div class="print-meta">
            <strong>{{ $order['po_number'] ?? '—' }}</strong>
            <div>Date: {{ $order['order_date'] ?? '—' }}</div>
            @if(!empty($order['expected_date']))
                <div>Expected: {{ $order['expected_date'] }}</div>
            @endif
            <div>Status: {{ ucfirst(str_replace('_', ' ', $order['status'] ?? 'draft')) }}</div>
        </div>
    </header>

    <div class="print-grid">
        <div>
            <h2 class="print-section-title">Supplier</h2>
            <div class="print-section-body">
                <p><strong>{{ $order['supplier']['name'] ?? '—' }}</strong></p>
                @if(!empty($order['supplier']['contact_person']))
                    <p>{{ $order['supplier']['contact_person'] }}</p>
                @endif
                @if(!empty($order['supplier']['email']))
                    <p>{{ $order['supplier']['email'] }}</p>
                @endif
                @if(!empty($order['supplier']['phone']))
                    <p>{{ $order['supplier']['phone'] }}</p>
                @endif
                @if(!empty($order['supplier']['address']))
                    <p>{{ $order['supplier']['address'] }}</p>
                @endif
            </div>
        </div>
        <div>
            <h2 class="print-section-title">Deliver to</h2>
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
                <th class="text-right">Qty ordered</th>
                <th class="text-right">Unit cost</th>
                <th class="text-right">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order['items'] ?? [] as $item)
                <tr>
                    <td>{{ $item['product']['name'] ?? 'Product #'.($item['product_id'] ?? '') }}</td>
                    <td>{{ $item['product']['sku'] ?? '—' }}</td>
                    <td class="text-right">{{ $item['quantity_ordered'] ?? 0 }}</td>
                    <td class="text-right">${{ number_format((float) ($item['unit_cost'] ?? 0), 2) }}</td>
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
