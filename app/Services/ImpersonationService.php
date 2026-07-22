<?php

namespace App\Services;

use App\Models\ImpersonationLog;
use App\Models\Organization;
use App\Models\PlatformAdmin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Laravel\Passport\Token;

class ImpersonationService
{
    public function start(PlatformAdmin $admin, Organization $organization, User $user, string $reason): array
    {
        if (! $user->organizations()->where('organizations.id', $organization->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => ['The selected user is not a member of this organization.'],
            ]);
        }

        return DB::transaction(function () use ($admin, $organization, $user, $reason): array {
            $this->endActiveSessionsForAdmin($admin);

            $tokenResult = $user->createToken('impersonation');

            $log = ImpersonationLog::query()->create([
                'platform_admin_id' => $admin->id,
                'organization_id' => $organization->id,
                'impersonated_user_id' => $user->id,
                'reason' => $reason,
                'token_id' => (string) $tokenResult->token->id,
                'started_at' => now(),
            ]);

            return [
                'log' => $log->load(['platformAdmin', 'impersonatedUser', 'organization']),
                'token' => [
                    'access_token' => $tokenResult->accessToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ],
                'organization_id' => $organization->id,
                'impersonation' => $this->formatSession($log),
            ];
        });
    }

    public function end(PlatformAdmin $admin, ?string $tokenId = null): ?ImpersonationLog
    {
        $query = ImpersonationLog::query()
            ->where('platform_admin_id', $admin->id)
            ->whereNull('ended_at');

        if ($tokenId !== null) {
            $query->where('token_id', $tokenId);
        }

        $log = $query->latest('started_at')->first();

        if ($log === null) {
            return null;
        }

        $log->forceFill(['ended_at' => now()])->save();

        if ($log->token_id !== null) {
            Token::query()->whereKey($log->token_id)->update(['revoked' => true]);
        }

        return $log->fresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    public function activeSessionForUser(User $user, ?string $accessToken = null): ?array
    {
        $tokenId = $this->extractTokenId($accessToken);

        if ($tokenId === null) {
            return null;
        }

        $log = ImpersonationLog::query()
            ->with(['platformAdmin', 'organization'])
            ->where('impersonated_user_id', $user->id)
            ->where('token_id', $tokenId)
            ->whereNull('ended_at')
            ->first();

        return $log ? $this->formatSession($log) : null;
    }

    protected function endActiveSessionsForAdmin(PlatformAdmin $admin): void
    {
        ImpersonationLog::query()
            ->where('platform_admin_id', $admin->id)
            ->whereNull('ended_at')
            ->get()
            ->each(function (ImpersonationLog $log): void {
                $log->forceFill(['ended_at' => now()])->save();

                if ($log->token_id !== null) {
                    Token::query()->whereKey($log->token_id)->update(['revoked' => true]);
                }
            });
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatSession(ImpersonationLog $log): array
    {
        return [
            'active' => $log->isActive(),
            'log_id' => $log->id,
            'platform_admin_id' => $log->platform_admin_id,
            'platform_admin_name' => $log->platformAdmin?->name,
            'organization_id' => $log->organization_id,
            'impersonated_user_id' => $log->impersonated_user_id,
            'reason' => $log->reason,
            'started_at' => $log->started_at?->toISOString(),
            'ended_at' => $log->ended_at?->toISOString(),
        ];
    }

    protected function extractTokenId(?string $accessToken): ?string
    {
        if ($accessToken === null || $accessToken === '') {
            return null;
        }

        try {
            $parts = explode('.', $accessToken);

            if (count($parts) !== 3) {
                return null;
            }

            $payload = json_decode(base64_decode(strtr($parts[1], '-_', '+/'), true), true);

            return isset($payload['jti']) ? (string) $payload['jti'] : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
