<?php

namespace App\Http\Middleware;

use App\Services\OrganizationSubscriptionService;
use App\Services\PlanLimitService;
use App\Support\PlanWarningCollector;
use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AppendPlanWarningMeta
{
    public function __construct(
        protected OrganizationSubscriptionService $subscriptionService,
        protected PlanLimitService $planLimitService,
        protected PlanWarningCollector $warningCollector,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (! $request->is('api/*') || ! app()->bound('currentOrganization')) {
            return $response;
        }

        /** @var \App\Models\Organization $organization */
        $organization = app('currentOrganization');

        $warning = $this->warningCollector->current()
            ?? $this->planLimitService->evaluateOrganizationWarnings($organization);

        if ($warning === null || ! $response instanceof JsonResponse) {
            return $response;
        }

        $payload = $response->getData(true);

        if (! is_array($payload)) {
            return $response;
        }

        $payload['meta'] = array_merge($payload['meta'] ?? [], [
            'plan_warning' => $warning,
        ]);

        $response->setData($payload);

        return $response;
    }
}
