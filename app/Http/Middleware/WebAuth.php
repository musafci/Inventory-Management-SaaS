<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Services\Web\WebSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class WebAuth
{
    public function __construct(
        protected WebSessionService $webSession,
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
            $activeOrganization = collect(session('organizations', []))
                ->first(fn (array $org): bool => (int) ($org['id'] ?? 0) === $organizationId);

            if (($activeOrganization['status'] ?? null) === OrganizationStatus::Suspended->value) {
                $this->webSession->clearAuthSession();

                return redirect('/login')->withErrors([
                    'email' => 'This organization has been suspended.',
                ]);
            }
        }

        return $next($request);
    }
}
