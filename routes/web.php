<?php

use App\Http\Controllers\Web\AuthController;
use App\Http\Controllers\Web\OrderPrintController;
use App\Http\Controllers\Web\PlatformAuthController;
use App\Http\Livewire\Platform\ActivityLogs as PlatformActivityLogs;
use App\Http\Livewire\Platform\Dashboard as PlatformDashboard;
use App\Http\Livewire\Platform\OrganizationShow as PlatformOrganizationShow;
use App\Http\Livewire\Platform\Organizations as PlatformOrganizations;
use App\Http\Livewire\Platform\PlatformAdmins as PlatformPlatformAdmins;
use Illuminate\Support\Facades\Route;
use Livewire\Livewire;

// Auth routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login']);
Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Protected routes
Route::middleware('web.auth')->group(function (): void {
    Route::post('/organization/switch', [AuthController::class, 'switchOrganization'])->name('organization.switch');
    Route::get('/', fn () => redirect('/dashboard'))->name('home');

    // Dashboard
    Route::get('/dashboard', \App\Http\Livewire\Dashboard::class)->name('dashboard');

    // Products
    Route::get('/products', \App\Http\Livewire\Products::class)->name('products.index');
    Route::get('/products/create', \App\Http\Livewire\Products::class)->name('products.create');
    Route::get('/products/{id}', \App\Http\Livewire\Products::class)->name('products.show');
    Route::get('/products/{id}/edit', \App\Http\Livewire\Products::class)->name('products.edit');

    // Categories
    Route::get('/categories', \App\Http\Livewire\Categories::class)->name('categories.index');
    Route::get('/categories/create', \App\Http\Livewire\Categories::class)->name('categories.create');

    // Units
    Route::get('/units', \App\Http\Livewire\Units::class)->name('units.index');
    Route::get('/units/create', \App\Http\Livewire\Units::class)->name('units.create');

    // Warehouses
    Route::get('/warehouses', \App\Http\Livewire\Warehouses::class)->name('warehouses.index');
    Route::get('/warehouses/create', \App\Http\Livewire\Warehouses::class)->name('warehouses.create');

    // Suppliers
    Route::get('/suppliers', \App\Http\Livewire\Suppliers::class)->name('suppliers.index');
    Route::get('/suppliers/create', \App\Http\Livewire\Suppliers::class)->name('suppliers.create');

    // Customers
    Route::get('/customers', \App\Http\Livewire\Customers::class)->name('customers.index');
    Route::get('/customers/create', \App\Http\Livewire\Customers::class)->name('customers.create');

    // Purchase Orders
    Route::get('/purchase-orders/{id}/print', [OrderPrintController::class, 'purchaseOrder'])->name('purchase-orders.print');
    Route::get('/purchase-orders', \App\Http\Livewire\PurchaseOrders::class)->name('purchase-orders.index');
    Route::get('/purchase-orders/create', \App\Http\Livewire\PurchaseOrders::class)->name('purchase-orders.create');
    Route::get('/purchase-orders/{id}', \App\Http\Livewire\PurchaseOrders::class)->name('purchase-orders.show');
    Route::get('/purchase-orders/{id}/edit', \App\Http\Livewire\PurchaseOrders::class)->name('purchase-orders.edit');

    // Sales Orders
    Route::get('/sales-orders/{id}/print', [OrderPrintController::class, 'salesOrder'])->name('sales-orders.print');
    Route::get('/sales-orders', \App\Http\Livewire\SalesOrders::class)->name('sales-orders.index');
    Route::get('/sales-orders/create', \App\Http\Livewire\SalesOrders::class)->name('sales-orders.create');
    Route::get('/sales-orders/{id}', \App\Http\Livewire\SalesOrders::class)->name('sales-orders.show');
    Route::get('/sales-orders/{id}/edit', \App\Http\Livewire\SalesOrders::class)->name('sales-orders.edit');

    // Stocks
    Route::get('/stocks', \App\Http\Livewire\Stocks::class)->name('stocks.index');

    // Stock Movements
    Route::get('/stock-movements', \App\Http\Livewire\StockMovements::class)->name('stock-movements.index');
    Route::get('/stock-movements/create', \App\Http\Livewire\StockMovements::class)->name('stock-movements.create');

    // Payments
    Route::get('/payments', \App\Http\Livewire\Payments::class)->name('payments.index');
    Route::get('/payments/{id}', \App\Http\Livewire\Payments::class)->name('payments.show');

    // Reports
    Route::get('/reports', \App\Http\Livewire\Reports::class)->name('reports.index');
    Route::get('/reports/stock-valuation', \App\Http\Livewire\Reports::class)->name('reports.stock-valuation');
    Route::get('/reports/low-stock', \App\Http\Livewire\Reports::class)->name('reports.low-stock');
    Route::get('/reports/sales-summary', \App\Http\Livewire\Reports::class)->name('reports.sales-summary');
    Route::get('/reports/purchase-summary', \App\Http\Livewire\Reports::class)->name('reports.purchase-summary');

    Route::get('/settings', function () {
        if (\App\Support\OrganizationSession::canManageOrganization()) {
            return redirect()->route('settings.organization');
        }

        if (\App\Support\OrganizationSession::canManageRoles()) {
            return redirect()->route('settings.roles');
        }

        if (\App\Support\OrganizationSession::canManageUsers()) {
            return redirect()->route('settings.team');
        }

        abort(403);
    })->name('settings');

    Route::get('/settings/organization', \App\Http\Livewire\OrganizationSettings::class)->name('settings.organization');
    Route::get('/settings/billing', \App\Http\Livewire\BillingSettings::class)->name('settings.billing');
    Route::get('/settings/team', \App\Http\Livewire\Users::class)->name('settings.team');
    Route::get('/settings/roles', \App\Http\Livewire\Roles::class)->name('settings.roles');
    Route::redirect('/users', '/settings/team');
});

// Platform super-admin portal (separate session from tenant app)
Route::prefix('platform')->group(function (): void {
    Route::get('/login', [PlatformAuthController::class, 'showLogin'])->name('platform.login');
    Route::post('/login', [PlatformAuthController::class, 'login'])->name('platform.login.submit');

    Route::middleware('platform.web.auth')->group(function (): void {
        Route::post('/logout', [PlatformAuthController::class, 'logout'])->name('platform.logout');
        Route::redirect('/', '/platform/dashboard');
        Route::get('/dashboard', PlatformDashboard::class)->name('platform.dashboard');
        Route::get('/organizations', PlatformOrganizations::class)->name('platform.organizations.index');
        Route::get('/organizations/{id}', PlatformOrganizationShow::class)->name('platform.organizations.show');
        Route::get('/activity-logs', PlatformActivityLogs::class)->name('platform.activity-logs.index');
        Route::get('/admins', PlatformPlatformAdmins::class)->name('platform.admins.index');
    });
});
