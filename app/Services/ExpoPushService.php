<?php

namespace App\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ExpoPushService
{
    /**
     * @param  list<string>  $tokens
     * @param  array<string, mixed>  $data
     */
    public function send(array $tokens, string $title, string $body, array $data = []): void
    {
        $tokens = array_values(array_unique(array_filter($tokens)));

        if ($tokens === []) {
            return;
        }

        $messages = array_map(
            fn (string $token): array => [
                'to' => $token,
                'title' => $title,
                'body' => $body,
                'data' => $data,
                'sound' => 'default',
            ],
            $tokens,
        );

        $response = Http::acceptJson()
            ->post('https://exp.host/--/api/v2/push/send', $messages);

        if (! $response->successful()) {
            Log::warning('Expo push delivery failed.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);
        }
    }

    /**
     * @param  Collection<int, \App\Models\User>  $users
     * @param  array<string, mixed>  $data
     */
    public function sendToUsers(Collection $users, int $organizationId, string $title, string $body, array $data = []): void
    {
        $tokens = \App\Models\DevicePushToken::query()
            ->whereIn('user_id', $users->pluck('id'))
            ->where(function ($query) use ($organizationId): void {
                $query->where('organization_id', $organizationId)
                    ->orWhereNull('organization_id');
            })
            ->pluck('expo_push_token')
            ->all();

        $this->send($tokens, $title, $body, $data);
    }
}
