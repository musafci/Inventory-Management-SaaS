<div class="flex h-16 items-center px-0 pt-6 mb-2">
    <a href="/dashboard" class="block transition-opacity hover:opacity-90">
        <x-app-logo size="sm" />
    </a>
</div>

<nav class="sidebar-scroll flex flex-1 flex-col gap-0.5 overflow-y-auto pb-4">
    @php $current = request()->route()?->getName() ?? ''; @endphp

    @if(\App\Support\OrganizationSession::canAny(['reports.view_inventory', 'reports.view_sales', 'reports.view_purchases', 'inventory.view']))
        <a href="/dashboard" class="nav-link {{ str_starts_with($current, 'dashboard') ? 'nav-link-active' : 'nav-link-inactive' }}">
            <svg class="h-5 w-5 flex-shrink-0" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" /></svg>
            Dashboard
        </a>
    @endif

    @if(\App\Support\OrganizationSession::can('inventory.view'))
        <div class="nav-section-label">Catalog</div>
        <a href="/products" class="nav-link {{ str_starts_with($current, 'products') ? 'nav-link-active' : 'nav-link-inactive' }}">Products</a>
        <a href="/categories" class="nav-link {{ str_starts_with($current, 'categories') ? 'nav-link-active' : 'nav-link-inactive' }}">Categories</a>
        <a href="/units" class="nav-link {{ str_starts_with($current, 'units') ? 'nav-link-active' : 'nav-link-inactive' }}">Units</a>

        <div class="nav-section-label">Warehouse</div>
        <a href="/warehouses" class="nav-link {{ str_starts_with($current, 'warehouses') ? 'nav-link-active' : 'nav-link-inactive' }}">Warehouses</a>
        <a href="/stocks" class="nav-link {{ str_starts_with($current, 'stocks') ? 'nav-link-active' : 'nav-link-inactive' }}">Stock Levels</a>
        <a href="/stock-movements" class="nav-link {{ str_starts_with($current, 'stock-movements') ? 'nav-link-active' : 'nav-link-inactive' }}">Stock Movements</a>
    @endif

    @if(\App\Support\OrganizationSession::canAny(['suppliers.view', 'orders.purchase.view']))
        <div class="nav-section-label">Purchasing</div>
        @canaccess('suppliers.view')
            <a href="/suppliers" class="nav-link {{ str_starts_with($current, 'suppliers') ? 'nav-link-active' : 'nav-link-inactive' }}">Suppliers</a>
        @endcanaccess
        @canaccess('orders.purchase.view')
            <a href="/purchase-orders" class="nav-link {{ str_starts_with($current, 'purchase-orders') ? 'nav-link-active' : 'nav-link-inactive' }}">Purchase Orders</a>
        @endcanaccess
    @endif

    @if(\App\Support\OrganizationSession::canAny(['customers.view', 'orders.sales.view', 'payments.view']))
        <div class="nav-section-label">Sales</div>
        @canaccess('customers.view')
            <a href="/customers" class="nav-link {{ str_starts_with($current, 'customers') ? 'nav-link-active' : 'nav-link-inactive' }}">Customers</a>
        @endcanaccess
        @canaccess('orders.sales.view')
            <a href="/sales-orders" class="nav-link {{ str_starts_with($current, 'sales-orders') ? 'nav-link-active' : 'nav-link-inactive' }}">Sales Orders</a>
        @endcanaccess
        @canaccess('payments.view')
            <a href="/payments" class="nav-link {{ str_starts_with($current, 'payments') ? 'nav-link-active' : 'nav-link-inactive' }}">Payments</a>
        @endcanaccess
    @endif

    @if(\App\Support\OrganizationSession::canAny(['reports.view_sales', 'reports.view_inventory', 'reports.view_purchases']))
        <div class="nav-section-label">Analytics</div>
        <a href="/reports" class="nav-link {{ str_starts_with($current, 'reports') ? 'nav-link-active' : 'nav-link-inactive' }}">Reports</a>
    @endif

    @if(\App\Support\OrganizationSession::canAccessSettings())
        <div class="nav-section-label">Settings</div>
        <a href="/settings" class="nav-link {{ str_starts_with($current, 'settings') ? 'nav-link-active' : 'nav-link-inactive' }}">Settings</a>
    @endif
</nav>
