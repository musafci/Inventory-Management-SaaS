<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Exceptions\SubscriptionAccessDeniedException;
use App\Exceptions\SubscriptionPaymentRequiredException;
use App\Models\Organization;
use App\Services\OrganizationSubscriptionService;
use App\Services\Web\WebSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebAuth
{
    public function __construct(
        protected WebSessionService $webSession,
        protected OrganizationSubscriptionService $subscriptionService,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! session()->has('auth_token')) {
            return redirect()->guest('/login');
        }

        if (! $this->webSession->refreshIfNeeded()) {
            return redirect()->guest('/login');
        }

        $this->webSession->normalizeSessionOrganizationsIfNeeded();
        $this->webSession->syncPermissionsForActiveOrganization();

        $organizationId = (int) session('organization_id', 0);

        if ($organizationId > 0) {
            $organization = Organization::query()->find($organizationId);

            if ($organization !== null && $organization->status === OrganizationStatus::Suspended) {
                $this->webSession->clearAuthSession();

                return redirect('/login')->withErrors([
                    'email' => 'This organization has been suspended.',
                ]);
            }

            if ($organization !== null && ! $this->isBillingExemptRoute($request)) {
                try {
                    if ($request->isMethodSafe()) {
                        $this->subscriptionService->assertAllowsTenantRead($organization);
                    } else {
                        $this->subscriptionService->assertAllowsTenantWrite($organization);
                    }
                } catch (SubscriptionPaymentRequiredException $exception) {
                    return redirect('/settings/billing')->withErrors([
                        'billing' => $exception->getMessage(),
                    ]);
                } catch (SubscriptionAccessDeniedException $exception) {
                    return redirect('/settings/billing')->withErrors([
                        'billing' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $next($request);
    }

    protected function isBillingExemptRoute(Request $request): bool
    {
        return $request->is('settings/billing*')
            || $request->is('logout')
            || $request->is('impersonation/exit');
    }
}
