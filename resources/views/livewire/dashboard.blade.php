<div>
    {{-- Welcome banner --}}
    <div class="page-banner">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="text-sm font-medium text-primary-100">Welcome back</p>
                <h2 class="mt-1 text-2xl font-bold sm:text-3xl">{{ session('user_name', 'there') }}</h2>
                <p class="mt-2 text-sm text-primary-100/80">Here's what's happening with your inventory today.</p>
            </div>
            <div class="flex gap-3">
                <a href="/products/create" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold text-white ring-1 ring-white/20 backdrop-blur-sm transition-all hover:bg-white/25">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                    Add Product
                </a>
                <a href="/reports" class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-primary-700 shadow-sm transition-all hover:bg-primary-50">
                    <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" /></svg>
                    View Reports
                </a>
            </div>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="mb-8 grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-4">
        <div class="stat-card stat-card-primary">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-primary-50 ring-1 ring-primary-100">
                    <svg class="h-6 w-6 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 003 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 005.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 009.568 3z" /><path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Total Products</p>
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['total_products'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="stat-card stat-card-emerald">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100">
                    <svg class="h-6 w-6 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5m6 4.125l2.25 2.25m0 0l2.25-2.25M12 13.875V7.5" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Stock Items</p>
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['total_stock_items'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="stat-card stat-card-amber">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 ring-1 ring-amber-100">
                    <svg class="h-6 w-6 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Purchase Orders</p>
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['pending_purchase_orders'] ?? 0) }}</p>
                </div>
            </div>
        </div>
        <div class="stat-card stat-card-sky">
            <div class="flex items-center gap-4">
                <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-sky-50 ring-1 ring-sky-100">
                    <svg class="h-6 w-6 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 3h1.386c.51 0 .955.343 1.087.835l.383 1.437M7.5 14.25a3 3 0 00-3 3h15.75m-12.75-3h11.218c1.121 0 2.09-.773 2.34-1.872l1.836-8.046A1.125 1.125 0 0018.054 3H5.106" /></svg>
                </div>
                <div>
                    <p class="text-sm font-medium text-slate-500">Sales Orders</p>
                    <p class="text-3xl font-bold tracking-tight text-slate-900">{{ number_format($stats['pending_sales_orders'] ?? 0) }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {{-- Low Stock Alert --}}
        <div class="card">
            <div class="card-header flex items-center justify-between">
                <h3 class="card-title">Low Stock Alerts</h3>
                <a href="/reports" class="text-xs font-semibold text-primary-600 hover:text-primary-500">View all</a>
            </div>
            <div class="p-6">
                @if(empty($lowStockItems))
                    <div class="empty-state">
                        <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-50 ring-1 ring-emerald-100">
                            <svg class="h-7 w-7 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <p class="mt-4 text-sm font-medium text-slate-900">All stock levels look good</p>
                        <p class="mt-1 text-sm text-slate-500">No items are below their reorder point.</p>
                    </div>
                @else
                    <div class="space-y-3">
                        @foreach($lowStockItems as $item)
                            <div class="flex items-center justify-between rounded-xl bg-amber-50/80 p-4 ring-1 ring-amber-100">
                                <div class="flex items-center gap-3">
                                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-amber-100">
                                        <svg class="h-4 w-4 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126z" /></svg>
                                    </div>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-900">{{ $item['product']['name'] ?? 'Product #' . ($item['product_id'] ?? '') }}</p>
                                        <p class="text-xs text-slate-500">Reorder point: {{ $item['reorder_point'] ?? 0 }}</p>
                                    </div>
                                </div>
                                <span class="badge badge-warning">
                                    {{ $item['quantity_available'] ?? 0 }} left
                                </span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>

        {{-- Quick Actions --}}
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">Quick Actions</h3>
            </div>
            <div class="p-6">
                <div class="grid grid-cols-2 gap-4">
                    <a href="/products/create" class="action-tile">
                        <div class="action-tile-icon bg-primary-50 ring-1 ring-primary-100">
                            <svg class="h-5 w-5 text-primary-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">New Product</span>
                    </a>
                    <a href="/purchase-orders/create" class="action-tile">
                        <div class="action-tile-icon bg-amber-50 ring-1 ring-amber-100">
                            <svg class="h-5 w-5 text-amber-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">Purchase Order</span>
                    </a>
                    <a href="/sales-orders/create" class="action-tile">
                        <div class="action-tile-icon bg-sky-50 ring-1 ring-sky-100">
                            <svg class="h-5 w-5 text-sky-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">Sales Order</span>
                    </a>
                    <a href="/stock-movements/create" class="action-tile">
                        <div class="action-tile-icon bg-emerald-50 ring-1 ring-emerald-100">
                            <svg class="h-5 w-5 text-emerald-600" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        </div>
                        <span class="text-sm font-semibold text-slate-700">Stock Adjustment</span>
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
