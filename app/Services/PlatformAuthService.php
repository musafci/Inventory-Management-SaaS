<?php

namespace App\Services;

use App\Models\PlatformAdmin;
use App\Support\PassportPersonalAccessClients;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Hash;

class PlatformAuthService
{
    /**
     * @return array{admin: PlatformAdmin, token: array<string, mixed>}
     */
    public function login(string $email, string $password): array
    {
        $admin = PlatformAdmin::query()->where('email', $email)->first();

        if ($admin === null || ! Hash::check($password, $admin->password)) {
            throw new AuthenticationException('Invalid platform credentials.');
        }

        PassportPersonalAccessClients::ensure('platform_admins');
        $tokenResult = $admin->createToken('platform-admin');

        return [
            'admin' => $admin,
            'token' => [
                'access_token' => $tokenResult->accessToken,
                'token_type' => 'Bearer',
                'expires_in' => 31536000,
            ],
        ];
    }

    public function logout(PlatformAdmin $admin): void
    {
        $token = $admin->token();

        if ($token !== null) {
            $token->revoke();
        }
    }
}
