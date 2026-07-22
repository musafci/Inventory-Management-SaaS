<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Exceptions\SubscriptionAccessDeniedException;
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

            if ($organization !== null) {
                try {
                    $this->subscriptionService->assertAllowsTenantAccess($organization);
                } catch (SubscriptionAccessDeniedException $exception) {
                    $this->webSession->clearAuthSession();

                    return redirect('/login')->withErrors([
                        'email' => $exception->getMessage(),
                    ]);
                }
            }
        }

        return $next($request);
    }
}
