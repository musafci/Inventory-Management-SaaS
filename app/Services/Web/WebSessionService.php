<?php

namespace App\Services\Web;

use App\Http\Resources\OrganizationResource;
use App\Models\Organization;
use App\Models\User;
use App\Services\PermissionAuthorizationService;
use App\Services\AuthService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;

class WebSessionService
{
    public function __construct(
        protected AuthService $authService,
        protected PermissionAuthorizationService $permissionAuthorization,
    ) {}

    /**
     * Persist OAuth tokens and related session data after login, register, or refresh.
     *
     * @param  array<string, mixed>  $token
     * @param  array<string, mixed>  $user
     * @param  Collection<int, Organization>|array<int, array<string, mixed>>  $organizations
     */
    public function storeAuthSession(array $token, array $user, Collection|array $organizations): void
    {
        $expiresIn = (int) ($token['expires_in'] ?? 3600);

        Session::put([
            'auth_token' => $token['access_token'] ?? '',
            'refresh_token' => $token['refresh_token'] ?? Session::get('refresh_token'),
            'token_expires_at' => now()->addSeconds($expiresIn)->toIso8601String(),
            'user_id' => $user['id'] ?? Session::get('user_id'),
            'user_name' => $user['name'] ?? Session::get('user_name', ''),
            'user_email' => $user['email'] ?? Session::get('user_email', ''),
            'organizations' => $this->normalizeOrganizations($organizations),
        ]);
    }

    /**
     * @param  Collection<int, Organization>|array<int, array<string, mixed>>  $organizations
     * @return array<int, array<string, mixed>>
     */
    public function normalizeOrganizations(Collection|array $organizations): array
    {
        return collect($organizations)
            ->map(function (Organization|array $organization): array {
                if ($organization instanceof Organization) {
                    return (new OrganizationResource($organization))->resolve(request());
                }

                return [
                    'id' => $organization['id'] ?? null,
                    'name' => $organization['name'] ?? null,
                    'slug' => $organization['slug'] ?? null,
                    'email' => $organization['email'] ?? null,
                    'phone' => $organization['phone'] ?? null,
                    'plan' => $organization['plan'] ?? null,
                    'status' => is_object($organization['status'] ?? null)
                        ? $organization['status']->value
                        : ($organization['status'] ?? null),
                    'trial_ends_at' => $organization['trial_ends_at'] ?? null,
                    'role' => $organization['role']
                        ?? ($organization['pivot']['role'] ?? null),
                ];
            })
            ->values()
            ->all();
    }

    public function normalizeSessionOrganizationsIfNeeded(): void
    {
        $organizations = Session::get('organizations', []);

        if ($organizations === []) {
            return;
        }

        $needsNormalization = collect($organizations)->contains(
            fn (array $organization): bool => ! array_key_exists('role', $organization)
                && isset($organization['pivot']['role']),
        );

        if ($needsNormalization) {
            Session::put('organizations', $this->normalizeOrganizations($organizations));
        }
    }

    public function setActiveOrganization(int $organizationId): bool
    {
        $organizations = collect(Session::get('organizations', []));

        if (! $organizations->contains(fn (array $org): bool => (int) ($org['id'] ?? 0) === $organizationId)) {
            return false;
        }

        Session::put('organization_id', $organizationId);
        $this->syncPermissionsForActiveOrganization();

        return true;
    }

    public function syncPermissionsForActiveOrganization(): void
    {
        $userId = (int) Session::get('user_id', 0);
        $organizationId = (int) Session::get('organization_id', 0);

        if ($userId === 0 || $organizationId === 0) {
            Session::forget('permissions');

            return;
        }

        $user = User::query()->find($userId);

        if ($user === null) {
            Session::forget('permissions');

            return;
        }

        Session::put(
            'permissions',
            $this->permissionAuthorization->permissionsForUser($user, $organizationId),
        );
    }

    /**
     * @param  array<string, mixed>  $organization
     */
    public function syncOrganization(array $organization): void
    {
        $organizationId = (int) ($organization['id'] ?? 0);

        if ($organizationId === 0) {
            return;
        }

        $organizations = collect(Session::get('organizations', []))
            ->map(function (array $org) use ($organization, $organizationId): array {
                if ((int) ($org['id'] ?? 0) !== $organizationId) {
                    return $org;
                }

                return array_merge($org, array_intersect_key($organization, array_flip([
                    'id', 'name', 'slug', 'email', 'phone', 'plan', 'status', 'trial_ends_at',
                ])));
            })
            ->values()
            ->all();

        Session::put('organizations', $organizations);
    }

    /**
     * Refresh the access token when it is expired or close to expiring.
     */
    public function refreshIfNeeded(): bool
    {
        if (! Session::has('auth_token')) {
            return false;
        }

        $expiresAt = Session::get('token_expires_at');
        if ($expiresAt && now()->addMinutes(5)->lt(\Illuminate\Support\Carbon::parse($expiresAt))) {
            return true;
        }

        $refreshToken = Session::get('refresh_token');
        if (! $refreshToken) {
            return Session::has('auth_token');
        }

        try {
            $result = $this->authService->refresh($refreshToken);

            $this->storeAuthSession(
                $result['token'],
                $result['user']->toArray(),
                $result['organizations'],
            );

            return true;
        } catch (\Throwable) {
            $this->clearAuthSession();

            return false;
        }
    }

    public function clearAuthSession(): void
    {
        Session::forget([
            'auth_token',
            'refresh_token',
            'token_expires_at',
            'organization_id',
            'user_id',
            'user_name',
            'user_email',
            'organizations',
            'permissions',
        ]);
    }
}
