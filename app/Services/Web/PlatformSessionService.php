<?php

namespace App\Services\Web;

use Illuminate\Support\Facades\Session;

class PlatformSessionService
{
    /**
     * @param  array<string, mixed>  $token
     * @param  array<string, mixed>  $admin
     */
    public function storeAuthSession(array $token, array $admin): void
    {
        Session::put([
            'platform_auth_token' => $token['access_token'] ?? '',
            'platform_admin_id' => $admin['id'] ?? null,
            'platform_admin_name' => $admin['name'] ?? '',
            'platform_admin_email' => $admin['email'] ?? '',
        ]);
    }

    public function clearAuthSession(): void
    {
        Session::forget([
            'platform_auth_token',
            'platform_admin_id',
            'platform_admin_name',
            'platform_admin_email',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function exportSession(): array
    {
        return Session::only([
            'platform_auth_token',
            'platform_admin_id',
            'platform_admin_name',
            'platform_admin_email',
        ]);
    }

    public function hasAuthToken(): bool
    {
        return Session::has('platform_auth_token')
            && Session::get('platform_auth_token') !== '';
    }
}
