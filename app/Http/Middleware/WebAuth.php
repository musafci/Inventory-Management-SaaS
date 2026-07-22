<?php

namespace App\Http\Middleware;

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

        return $next($request);
    }
}
