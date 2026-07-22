<?php

namespace App\Services;

use App\Models\User;
use App\Permission\PermissionCatalog;
use Illuminate\Support\Facades\Cache;

class PermissionAuthorizationService
{
    /**
     * @return list<string>
     */
    public function permissionsForUser(User $user, int $organizationId): array
    {
        return Cache::remember(
            $this->cacheKey($user->id, $organizationId),
            now()->addMinutes(30),
            function () use ($user, $organizationId): array {
                setPermissionsTeamId($organizationId);

                if ($user->hasRole(PermissionCatalog::SYSTEM_OWNER_ROLE)) {
                    return PermissionCatalog::all();
                }

                return $user->getAllPermissions()
                    ->pluck('name')
                    ->values()
                    ->all();
            },
        );
    }

    public function userCan(User $user, int $organizationId, string $permission): bool
    {
        setPermissionsTeamId($organizationId);

        if ($user->hasRole(PermissionCatalog::SYSTEM_OWNER_ROLE)) {
            return true;
        }

        return $user->can($permission);
    }

    public function forgetUserOrganizationCache(int $userId, int $organizationId): void
    {
        Cache::forget($this->cacheKey($userId, $organizationId));
    }

    public function forgetOrganizationCache(int $organizationId): void
    {
        $prefix = "permissions:user:*:org:{$organizationId}";

        if (Cache::getStore() instanceof \Illuminate\Cache\RedisStore) {
            // Best-effort flush for redis tagged keys is unavailable; rely on Spatie cache flush.
            return;
        }
    }

    protected function cacheKey(int $userId, int $organizationId): string
    {
        return "permissions:user:{$userId}:org:{$organizationId}";
    }
}
