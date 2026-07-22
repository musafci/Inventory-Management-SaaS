<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Laravel\Passport\Token;

class SessionService
{
    /**
     * @return Collection<int, array{id: string, name: ?string, created_at: ?string, expires_at: ?string, is_current: bool}>
     */
    public function listActiveSessions(User $user, ?string $currentTokenId = null): Collection
    {
        return Token::query()
            ->where('user_id', $user->id)
            ->where('revoked', false)
            ->where(function ($query): void {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->orderByDesc('created_at')
            ->get()
            ->map(fn (Token $token): array => [
                'id' => (string) $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at?->toIso8601String(),
                'expires_at' => $token->expires_at?->toIso8601String(),
                'is_current' => $currentTokenId !== null && (string) $token->id === $currentTokenId,
            ]);
    }

    public function revokeSession(User $user, string $tokenId): void
    {
        $token = Token::query()
            ->where('user_id', $user->id)
            ->whereKey($tokenId)
            ->firstOrFail();

        $token->revoke();

        \Laravel\Passport\RefreshToken::query()
            ->where('access_token_id', $token->id)
            ->update(['revoked' => true]);
    }
}
