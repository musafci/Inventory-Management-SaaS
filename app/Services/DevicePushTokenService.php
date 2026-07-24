<?php

namespace App\Services;

use App\Models\DevicePushToken;
use App\Models\User;

class DevicePushTokenService
{
    /**
     * @param  array{
     *     expo_push_token: string,
     *     platform: string,
     *     device_name?: string|null,
     *     organization_id?: int|null,
     * }  $data
     */
    public function register(User $user, array $data): DevicePushToken
    {
        $organizationId = $data['organization_id'] ?? null;

        if ($organizationId !== null && ! $user->organizations()->whereKey($organizationId)->exists()) {
            abort(403, 'You are not a member of this organization.');
        }

        return DevicePushToken::query()->updateOrCreate(
            ['expo_push_token' => $data['expo_push_token']],
            [
                'user_id' => $user->id,
                'organization_id' => $organizationId,
                'platform' => $data['platform'],
                'device_name' => $data['device_name'] ?? null,
                'last_used_at' => now(),
            ],
        );
    }

    public function unregister(User $user, string $expoPushToken): bool
    {
        return DevicePushToken::query()
            ->where('user_id', $user->id)
            ->where('expo_push_token', $expoPushToken)
            ->delete() > 0;
    }
}
