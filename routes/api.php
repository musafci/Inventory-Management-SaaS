<?php

use App\Http\Controllers\Api\V1\BillingController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\CustomerController;
use App\Http\Controllers\Api\V1\OrganizationDataExportController;
use App\Http\Controllers\Api\V1\PurchaseOrderController;
use App\Http\Controllers\Api\V1\ReportController;
use App\Http\Controllers\Api\V1\SalesOrderController;
use App\Http\Controllers\Api\V1\SupplierController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\OrganizationController;
use App\Http\Controllers\Api\V1\OrganizationMemberController;
use App\Http\Controllers\Api\V1\ReportExportController;
use App\Http\Controllers\Api\V1\RoleController;
use App\Http\Controllers\Api\V1\ProductAuthorizationProbeController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\StockController;
use App\Http\Controllers\Api\V1\StockMovementController;
use App\Http\Controllers\Api\V1\UnitController;
use App\Http\Controllers\Api\V1\WarehouseController;
use App\Http\Controllers\StripeWebhookController;
use App\Http\Controllers\HealthController;
use Illuminate\Support\Facades\Route;

Route::get('health', HealthController::class);

Route::get('organization-exports/{export}/download', [OrganizationDataExportController::class, 'download'])
    ->name('organization-export.download');

Route::post('stripe/webhook', [StripeWebhookController::class, 'handle']);

Route::prefix('v1')->group(function (): void {
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register'])
            ->middleware('throttle:auth-register');
        Route::post('login', [AuthController::class, 'login'])
            ->middleware('throttle:auth-login');
        Route::post('refresh', [AuthController::class, 'refresh']);
        Route::post('forgot-password', [AuthController::class, 'forgotPassword'])
            ->middleware('throttle:auth-forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])
            ->middleware('throttle:auth-forgot-password');

        Route::middleware('auth:api')->group(function (): void {
            Route::get('me', [AuthController::class, 'me']);
            Route::post('logout', [AuthController::class, 'logout']);
            Route::get('sessions', [AuthController::class, 'sessions']);
            Route::delete('sessions/{tokenId}', [AuthController::class, 'revokeSession']);
        });
    });

    Route::middleware(['auth:api', 'tenant', 'throttle:api-tenant'])->group(function (): void {
        Route::apiResource('suppliers', SupplierController::class)
            ->parameters(['suppliers' => 'supplierId']);

        Route::post('purchase-orders', [PurchaseOrderController::class, 'store'])
            ->middleware('idempotency');
        Route::apiResource('purchase-orders', PurchaseOrderController::class)
            ->parameters(['purchase-orders' => 'purchaseOrderId'])
            ->except(['store']);

        Route::post('purchase-orders/{purchaseOrderId}/send', [PurchaseOrderController::class, 'send']);
        Route::post('purchase-orders/{purchaseOrderId}/cancel', [PurchaseOrderController::class, 'cancel']);
        Route::post('purchase-orders/{purchaseOrderId}/receive', [PurchaseOrderController::class, 'receive']);
        Route::post('purchase-orders/{purchaseOrderId}/pay', [PurchaseOrderController::class, 'pay']);

        Route::get('payments', [PaymentController::class, 'index']);
        Route::get('payments/{paymentId}', [PaymentController::class, 'show']);

        Route::get('roles/permissions', [RoleController::class, 'permissions']);
        Route::apiResource('roles', RoleController::class)
            ->parameters(['roles' => 'roleId'])
            ->except(['show']);

        Route::get('organization', [OrganizationController::class, 'show']);
        Route::patch('organization', [OrganizationController::class, 'update']);
        Route::post('organization/export', [OrganizationDataExportController::class, 'store']);
        Route::post('organization/request-deletion', [OrganizationController::class, 'requestDeletion']);
        Route::post('organization/cancel-deletion', [OrganizationController::class, 'cancelDeletion']);

        Route::prefix('billing')->group(function (): void {
            Route::get('/', [BillingController::class, 'show']);
            Route::post('checkout', [BillingController::class, 'checkout']);
            Route::post('portal', [BillingController::class, 'portal']);
        });

        Route::apiResource('users', OrganizationMemberController::class)
            ->parameters(['users' => 'userId'])
            ->except(['show']);

        Route::apiResource('customers', CustomerController::class)
            ->parameters(['customers' => 'customerId']);

        Route::post('sales-orders', [SalesOrderController::class, 'store'])
            ->middleware('idempotency');
        Route::apiResource('sales-orders', SalesOrderController::class)
            ->parameters(['sales-orders' => 'salesOrderId'])
            ->except(['store']);

        Route::post('sales-orders/{salesOrderId}/confirm', [SalesOrderController::class, 'confirm']);
        Route::post('sales-orders/{salesOrderId}/cancel', [SalesOrderController::class, 'cancel']);
        Route::post('sales-orders/{salesOrderId}/fulfill', [SalesOrderController::class, 'fulfill']);
        Route::post('sales-orders/{salesOrderId}/deliver', [SalesOrderController::class, 'deliver']);
        Route::post('sales-orders/{salesOrderId}/pay', [SalesOrderController::class, 'pay']);
        Route::post('sales-orders/{salesOrderId}/refund', [SalesOrderController::class, 'refund']);

        Route::post('products/authorization-probe', [ProductAuthorizationProbeController::class, 'store'])
            ->middleware('permission:inventory.create,api');

        Route::apiResource('products', ProductController::class)
            ->parameters(['products' => 'productId']);

        Route::apiResource('warehouses', WarehouseController::class)
            ->parameters(['warehouses' => 'warehouseId']);

        Route::apiResource('categories', CategoryController::class)
            ->parameters(['categories' => 'categoryId']);

        Route::apiResource('units', UnitController::class)
            ->parameters(['units' => 'unitId']);

        Route::get('stocks', [StockController::class, 'index']);
        Route::get('stock-movements', [StockMovementController::class, 'index']);
        Route::post('stock-movements', [StockMovementController::class, 'store']);

        Route::prefix('reports')->group(function (): void {
            Route::get('dashboard', [ReportController::class, 'dashboard']);
            Route::get('stock-valuation', [ReportController::class, 'stockValuation']);
            Route::get('low-stock', [ReportController::class, 'lowStock']);
            Route::get('sales-summary', [ReportController::class, 'salesSummary']);
            Route::get('purchase-summary', [ReportController::class, 'purchaseSummary']);
            Route::get('exports', [ReportExportController::class, 'index']);
            Route::post('exports', [ReportExportController::class, 'store']);
            Route::get('exports/{exportId}', [ReportExportController::class, 'show']);
            Route::get('exports/{exportId}/download', [ReportExportController::class, 'download']);
        });
    });
});
