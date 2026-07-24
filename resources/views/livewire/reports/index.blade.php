<div>
    {{-- Tab Navigation --}}
    <div class="mb-6">
        <nav class="tab-nav" aria-label="Tabs">
            <button wire:click="switchTab('stock-valuation')" class="tab-btn {{ $activeTab === 'stock-valuation' ? 'tab-btn-active' : 'tab-btn-inactive' }}">
                <span class="hidden sm:inline">Stock Valuation</span>
                <span class="sm:hidden">Valuation</span>
            </button>
            <button wire:click="switchTab('low-stock')" class="tab-btn {{ $activeTab === 'low-stock' ? 'tab-btn-active' : 'tab-btn-inactive' }}">
                <span class="hidden sm:inline">Low Stock</span>
                <span class="sm:hidden">Low Stock</span>
            </button>
            <button wire:click="switchTab('sales-summary')" class="tab-btn {{ $activeTab === 'sales-summary' ? 'tab-btn-active' : 'tab-btn-inactive' }}">
                <span class="hidden sm:inline">Sales Summary</span>
                <span class="sm:hidden">Sales</span>
            </button>
            <button wire:click="switchTab('purchase-summary')" class="tab-btn {{ $activeTab === 'purchase-summary' ? 'tab-btn-active' : 'tab-btn-inactive' }}">
                <span class="hidden sm:inline">Purchase Summary</span>
                <span class="sm:hidden">Purchases</span>
            </button>
        </nav>
    </div>

    {{-- Filters --}}
    @if(in_array($activeTab, ['sales-summary', 'purchase-summary', 'stock-valuation', 'low-stock']))
        <div class="card p-4 mb-6">
            <form wire:submit.prevent="applyFilters" class="flex flex-col gap-4 sm:flex-row sm:items-end">
                @if(in_array($activeTab, ['sales-summary', 'purchase-summary']))
                    <div>
                        <label class="form-label">From</label>
                        <input type="date" wire:model="dateFrom" class="form-input">
                    </div>
                    <div>
                        <label class="form-label">To</label>
                        <input type="date" wire:model="dateTo" class="form-input">
                    </div>
                @endif
                <div>
                    <label class="form-label">Warehouse</label>
                    <select wire:model="warehouseId" class="form-input">
                        <option value="">All Warehouses</option>
                    </select>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="btn-primary">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-5.197-5.197m0 0A7.5 7.5 0 105.196 5.196a7.5 7.5 0 0010.607 10.607z" /></svg>
                        Apply Filters
                    </button>
                    <button type="button" wire:click="exportCsv" class="btn-secondary">Export CSV</button>
                </div>
            </form>
        </div>
    @endif

    {{-- Stock Valuation Tab --}}
    @if($activeTab === 'stock-valuation')
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">Stock Valuation Report</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Warehouse</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Unit Cost</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total Value</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($stockValuation as $item)
                            <tr class="table-row-hover">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $item['product']['name'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $item['warehouse']['name'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">{{ $item['quantity'] ?? 0 }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-900">${{ number_format($item['unit_cost'] ?? 0, 2) }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-semibold text-gray-900">${{ number_format($item['total_value'] ?? 0, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 15.75V18m-7.5-6.75h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm0 2.25h.008v.008H8.25v-.008zm2.498-6.75h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007v-.008zm0 2.25h.007v.008h-.007v-.008zm2.504-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008v-.008zm2.498-6.75h.008v.008h-.008v-.008zm0 2.25h.008v.008h-.008v-.008zM8.25 6h7.5v2.25h-7.5V6zM12 2.25c-1.892 0-3.758.11-5.593.322C5.307 2.7 4.5 3.65 4.5 4.757V19.5a2.25 2.25 0 002.25 2.25h10.5a2.25 2.25 0 002.25-2.25V4.757c0-1.108-.806-2.057-1.907-2.185A48.507 48.507 0 0012 2.25z" /></svg>
                                    <p class="mt-2 text-sm text-gray-500">No stock valuation data available.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if(!empty($stockValuation))
                        <tfoot class="bg-gray-50 border-t border-gray-200">
                            <tr>
                                <td colspan="4" class="px-6 py-4 text-sm font-semibold text-gray-900">Total Valuation</td>
                                <td class="px-6 py-4 text-sm font-bold text-gray-900">${{ number_format(collect($stockValuation)->sum('total_value'), 2) }}</td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>
    @endif

    {{-- Low Stock Tab --}}
    @if($activeTab === 'low-stock')
        <div class="card overflow-hidden">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">Low Stock Report</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Product</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Current Quantity</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Reorder Point</th>
                            <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 bg-white">
                        @forelse($lowStock as $item)
                            @php
                                $qty = $item['quantity_available'] ?? $item['quantity'] ?? 0;
                                $reorder = $item['reorder_point'] ?? 0;
                                $isOut = $qty <= 0;
                            @endphp
                            <tr class="table-row-hover">
                                <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">{{ $item['product']['name'] ?? '-' }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm {{ $isOut ? 'font-semibold text-red-600' : 'text-gray-900' }}">{{ $qty }}</td>
                                <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $reorder }}</td>
                                <td class="whitespace-nowrap px-6 py-4">
                                    @if($isOut)
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-0.5 text-xs font-medium text-red-800">Out of Stock</span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-medium text-amber-800">Low Stock</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-12 text-center">
                                    <svg class="mx-auto h-12 w-12 text-emerald-400" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                                    <p class="mt-2 text-sm text-gray-500">All stock levels look good! No low stock items found.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    {{-- Sales Summary Tab --}}
    @if($activeTab === 'sales-summary')
        @php $summary = is_array($salesSummary) ? $salesSummary : []; @endphp
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
            <div class="card p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100">
                        <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_orders'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-100">
                        <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Revenue</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($summary['total_revenue'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100">
                        <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Average Order Value</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($summary['average_order_value'] ?? $summary['avg_order'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($summary['recent_orders']) && count($summary['recent_orders']) > 0)
            <div class="card overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Recent Orders</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Order #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Customer</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($summary['recent_orders'] as $order)
                                <tr class="table-row-hover">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-primary-600">{{ $order['order_number'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $order['customer']['name'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $order['order_date'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">${{ number_format($order['total'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif

    {{-- Purchase Summary Tab --}}
    @if($activeTab === 'purchase-summary')
        @php $summary = is_array($purchaseSummary) ? $purchaseSummary : []; @endphp
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-3 mb-6">
            <div class="card p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-100">
                        <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Orders</p>
                        <p class="text-2xl font-bold text-gray-900">{{ number_format($summary['total_orders'] ?? 0) }}</p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-red-100">
                        <svg class="h-6 w-6 text-red-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 18.75a60.07 60.07 0 0115.797 2.101c.727.198 1.453-.342 1.453-1.096V18.75M3.75 4.5v.75A.75.75 0 013 6h-.75m0 0v-.375c0-.621.504-1.125 1.125-1.125H20.25M2.25 6v9m18-10.5v.75c0 .414.336.75.75.75h.75m-1.5-1.5h.375c.621 0 1.125.504 1.125 1.125v9.75c0 .621-.504 1.125-1.125 1.125h-.375m1.5-1.5H21a.75.75 0 00-.75.75v.75m0 0H3.75m0 0h-.375a1.125 1.125 0 01-1.125-1.125V15m1.5 1.5v-.75A.75.75 0 003 15h-.75M15 10.5a3 3 0 11-6 0 3 3 0 016 0zm3 0h.008v.008H18V10.5zm-12 0h.008v.008H6V10.5z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Total Spent</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($summary['total_spent'] ?? $summary['total_amount'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
            <div class="card p-6">
                <div class="flex items-center gap-4">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary-100">
                        <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    </div>
                    <div>
                        <p class="text-sm font-medium text-gray-500">Average Order Value</p>
                        <p class="text-2xl font-bold text-gray-900">${{ number_format($summary['average_order_value'] ?? $summary['avg_order'] ?? 0, 2) }}</p>
                    </div>
                </div>
            </div>
        </div>

        @if(isset($summary['recent_orders']) && count($summary['recent_orders']) > 0)
            <div class="card overflow-hidden">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h3 class="text-base font-semibold text-gray-900">Recent Purchase Orders</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">PO #</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Supplier</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Date</th>
                                <th class="px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">Total</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200 bg-white">
                            @foreach($summary['recent_orders'] as $order)
                                <tr class="table-row-hover">
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-primary-600">{{ $order['po_number'] ?? $order['order_number'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $order['supplier']['name'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-500">{{ $order['order_date'] ?? '-' }}</td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm font-medium text-gray-900">${{ number_format($order['total'] ?? 0, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>
