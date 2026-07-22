<?php

namespace App\Http\Middleware;

use App\Services\Web\PlatformSessionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PlatformWebAuth
{
    public function __construct(
        protected PlatformSessionService $platformSession,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        if (! $this->platformSession->hasAuthToken()) {
            return redirect()->guest('/platform/login');
        }

        return $next($request);
    }
}
