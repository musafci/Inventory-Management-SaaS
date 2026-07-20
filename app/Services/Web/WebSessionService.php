<?php

namespace App\Services\Web;

use App\Services\AuthService;
use Illuminate\Support\Facades\Session;

class WebSessionService
{
    public function __construct(
        protected AuthService $authService,
    ) {}

    /**
     * Persist OAuth tokens and related session data after login, register, or refresh.
     *
     * @param  array<string, mixed>  $token
     * @param  array<string, mixed>  $user
     * @param  array<int, array<string, mixed>>  $organizations
     */
    public function storeAuthSession(array $token, array $user, array $organizations): void
    {
        $expiresIn = (int) ($token['expires_in'] ?? 3600);

        Session::put([
            'auth_token' => $token['access_token'] ?? '',
            'refresh_token' => $token['refresh_token'] ?? Session::get('refresh_token'),
            'token_expires_at' => now()->addSeconds($expiresIn)->toIso8601String(),
            'user_name' => $user['name'] ?? Session::get('user_name', ''),
            'user_email' => $user['email'] ?? Session::get('user_email', ''),
            'organizations' => $organizations,
        ]);
    }

    public function setActiveOrganization(int $organizationId): bool
    {
        $organizations = collect(Session::get('organizations', []));

        if (! $organizations->contains(fn (array $org): bool => (int) ($org['id'] ?? 0) === $organizationId)) {
            return false;
        }

        Session::put('organization_id', $organizationId);

        return true;
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
                $result['organizations']->toArray(),
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
            'user_name',
            'user_email',
            'organizations',
        ]);
    }
}
