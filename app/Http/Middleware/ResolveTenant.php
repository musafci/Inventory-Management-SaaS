<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Resolve the active organization from the X-Organization-Id header.
     */
    public function handle(Request $request, Closure $next): Response
    {
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

        app()->instance('currentOrganization', $organization);

        setPermissionsTeamId($organization->id);

        return $next($request);
    }
}
