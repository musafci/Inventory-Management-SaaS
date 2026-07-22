<?php

namespace App\Http\Middleware;

use App\Enums\OrganizationStatus;
use App\Exceptions\SubscriptionAccessDeniedException;
use App\Exceptions\SubscriptionPaymentRequiredException;
use App\Models\Organization;
use App\Services\OrganizationSubscriptionService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    public function __construct(
        protected OrganizationSubscriptionService $subscriptionService,
    ) {}

    /**
     * Resolve the active organization from the X-Organization-Id header.
     */
    public function handle(Request $request, Closure $next): Response
    {
        app()->forgetInstance('currentOrganization');

        $organizationId = $request->header('X-Organization-Id');

        if ($organizationId === null || $organizationId === '') {
            return response()->json([
                'message' => 'Organization context is required.',
            ], Response::HTTP_FORBIDDEN);
        }

        $user = $request->user();

        if ($user === null) {
            return response()->json([
                'message' => 'Unauthenticated.',
            ], Response::HTTP_UNAUTHORIZED);
        }

        $organization = Organization::query()->find($organizationId);

        if ($organization === null) {
            return response()->json([
                'message' => 'You do not belong to this organization.',
            ], Response::HTTP_FORBIDDEN);
        }

        $belongsToOrganization = $user->organizations()
            ->where('organizations.id', $organization->id)
            ->exists();

        if (! $belongsToOrganization) {
            return response()->json([
                'message' => 'You do not belong to this organization.',
            ], Response::HTTP_FORBIDDEN);
        }

        if ($organization->status === OrganizationStatus::Suspended) {
            return response()->json([
                'message' => 'This organization has been suspended.',
            ], Response::HTTP_FORBIDDEN);
        }

        if (! $this->isBillingExemptRoute($request)) {
            try {
                if ($request->isMethodSafe()) {
                    $this->subscriptionService->assertAllowsTenantRead($organization);
                } else {
                    $this->subscriptionService->assertAllowsTenantWrite($organization);
                }
            } catch (SubscriptionPaymentRequiredException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => [],
                ], Response::HTTP_PAYMENT_REQUIRED);
            } catch (SubscriptionAccessDeniedException $exception) {
                return response()->json([
                    'message' => $exception->getMessage(),
                    'errors' => [],
                ], Response::HTTP_FORBIDDEN);
            }
        }

        app()->instance('currentOrganization', $organization);

        setPermissionsTeamId($organization->id);

        return $next($request);
    }

    protected function isBillingExemptRoute(Request $request): bool
    {
        return $request->is('api/v1/billing')
            || $request->is('api/v1/billing/*');
    }
}
