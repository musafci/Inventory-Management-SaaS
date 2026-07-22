<?php

namespace App\Providers;

use App\Events\StockLevelChanged;
use App\Listeners\CheckLowStock;
use App\Models\Category;
use App\Models\Customer;
use App\Models\Organization;
use App\Models\Payment;
use App\Models\Product;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\Role;
use App\Models\User;
use App\Models\Warehouse;
use App\Observers\StockMovementObserver;
use App\Permission\PermissionCatalog;
use App\Policies\OrganizationMemberPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\CustomerPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\OrganizationPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseOrderPolicy;
use App\Policies\SalesOrderPolicy;
use App\Policies\SupplierPolicy;
use App\Policies\StockMovementPolicy;
use App\Policies\StockPolicy;
use App\Policies\UnitPolicy;
use App\Policies\WarehousePolicy;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Http\Exceptions\ThrottleRequestsException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Exceptions\OAuthServerException;
use Laravel\Passport\Passport;
use Spatie\Permission\Exceptions\UnauthorizedException;
use Symfony\Component\HttpFoundation\Response as HttpResponse;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        if ($appUrl = config('app.url')) {
            URL::forceRootUrl($appUrl);
        }

        Passport::enablePasswordGrant();

        RateLimiter::for('api-tenant', function (Request $request): Limit {
            $organizationId = (string) $request->header('X-Organization-Id', 'missing');
            $userId = (string) ($request->user()?->id ?? 'guest');

            return Limit::perMinute(config('api.rate_limit_per_minute', 120))
                ->by("org:{$organizationId}:user:{$userId}");
        });

        Gate::before(function (User $user, string $ability): ?bool {
            if (! app()->bound('currentOrganization')) {
                return null;
            }

            setPermissionsTeamId(app('currentOrganization')->id);

            if ($user->hasRole(PermissionCatalog::SYSTEM_OWNER_ROLE)) {
                return true;
            }

            return null;
        });

        Gate::policy(User::class, OrganizationMemberPolicy::class);
        Gate::policy(Organization::class, OrganizationPolicy::class);
        Gate::policy(Role::class, \App\Policies\RolePolicy::class);
        Gate::policy(Warehouse::class, WarehousePolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(Unit::class, UnitPolicy::class);
        Gate::policy(Product::class, ProductPolicy::class);
        Gate::policy(Supplier::class, SupplierPolicy::class);
        Gate::policy(PurchaseOrder::class, PurchaseOrderPolicy::class);
        Gate::policy(Customer::class, CustomerPolicy::class);
        Gate::policy(SalesOrder::class, SalesOrderPolicy::class);
        Gate::policy(Payment::class, PaymentPolicy::class);
        Gate::define('viewAnyDashboard', fn (User $user): bool => $user->can('reports.view_inventory')
            || $user->can('reports.view_sales')
            || $user->can('reports.view_purchases')
            || $user->can('inventory.view'));

        Gate::define('viewInventoryReports', fn (User $user): bool => $user->can('reports.view_inventory'));

        Gate::define('viewSalesReports', fn (User $user): bool => $user->can('reports.view_sales'));

        Gate::define('viewPurchaseReports', fn (User $user): bool => $user->can('reports.view_purchases'));

        Gate::define('exportReports', fn (User $user): bool => $user->can('reports.export'));
        Gate::policy(Stock::class, StockPolicy::class);
        Gate::policy(StockMovement::class, StockMovementPolicy::class);

        StockMovement::observe(StockMovementObserver::class);

        Event::listen(StockLevelChanged::class, CheckLowStock::class);

        Blade::if('canaccess', fn (string $permission): bool => \App\Support\OrganizationSession::can($permission));

        Response::macro('api', function (mixed $data = null, array $meta = [], int $status = HttpResponse::HTTP_OK) {
            $payload = ['data' => $data];

            if ($meta !== []) {
                $payload['meta'] = $meta;
            }

            return response()->json($payload, $status);
        });

        Response::macro('apiError', function (string $message, array $errors = [], int $status = HttpResponse::HTTP_BAD_REQUEST) {
            return response()->json([
                'message' => $message,
                'errors' => $errors,
            ], $status);
        });
    }

    public static function registerApiExceptionRendering(Exceptions $exceptions): void
    {
        $exceptions->render(function (ValidationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => 'The given data was invalid.',
                'errors' => $exception->errors(),
            ], HttpResponse::HTTP_UNPROCESSABLE_ENTITY);
        });

        $exceptions->render(function (AuthenticationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'Unauthenticated.',
                'errors' => [],
            ], HttpResponse::HTTP_UNAUTHORIZED);
        });

        $exceptions->render(function (AuthorizationException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage() ?: 'This action is unauthorized.',
                'errors' => [],
            ], HttpResponse::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (UnauthorizedException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            return response()->json([
                'message' => $exception->getMessage(),
                'errors' => [],
            ], HttpResponse::HTTP_FORBIDDEN);
        });

        $exceptions->render(function (OAuthServerException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $payload = json_decode($exception->getResponse()->getContent(), true) ?? [];

            return response()->json([
                'message' => $payload['message'] ?? 'Authentication failed.',
                'errors' => [],
            ], $exception->getResponse()->getStatusCode());
        });

        $exceptions->render(function (ThrottleRequestsException $exception, Request $request) {
            if (! $request->is('api/*')) {
                return null;
            }

            $headers = $exception->getHeaders();
            $retryAfter = $headers['Retry-After'] ?? $exception->getHeaders()['X-RateLimit-Reset'] ?? null;

            $response = response()->json([
                'message' => 'Too many requests.',
                'errors' => [],
            ], HttpResponse::HTTP_TOO_MANY_REQUESTS);

            if ($retryAfter !== null) {
                $response->headers->set('Retry-After', (string) $retryAfter);
            }

            if (isset($headers['X-RateLimit-Limit'])) {
                $response->headers->set('X-RateLimit-Limit', (string) $headers['X-RateLimit-Limit']);
            }

            if (isset($headers['X-RateLimit-Remaining'])) {
                $response->headers->set('X-RateLimit-Remaining', (string) $headers['X-RateLimit-Remaining']);
            }

            return $response;
        });
    }
}
