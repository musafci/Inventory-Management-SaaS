<?php

namespace App\Support;

use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class PassportPersonalAccessClients
{
    /**
     * @var list<array{name: string, provider: string}>
     */
    private const CLIENTS = [
        ['name' => 'User Personal Access Client', 'provider' => 'users'],
        ['name' => 'Platform Admin Personal Access Client', 'provider' => 'platform_admins'],
    ];

    public static function ensure(string $provider): void
    {
        if (self::find($provider) !== null) {
            return;
        }

        $config = collect(self::CLIENTS)->first(
            fn (array $client): bool => $client['provider'] === $provider,
        );

        if ($config === null) {
            return;
        }

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            $config['name'],
            $config['provider'],
        );
    }

    public static function ensureAll(): void
    {
        foreach (self::CLIENTS as $client) {
            self::ensure($client['provider']);
        }
    }

    public static function find(string $provider): ?Client
    {
        return Passport::client()->newQuery()
            ->where('revoked', false)
            ->where(function ($query) use ($provider): void {
                $query->when(
                    $provider === config('auth.guards.api.provider'),
                    fn ($builder) => $builder->orWhereNull('provider'),
                )->orWhere('provider', $provider);
            })
            ->latest()
            ->get()
            ->first(fn (Client $client): bool => $client->hasGrantType('personal_access'));
    }

    public static function exists(string $provider): bool
    {
        return self::find($provider) !== null;
    }
}
