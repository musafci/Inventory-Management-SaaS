<?php

namespace Tests\Concerns;

use Illuminate\Support\Facades\Artisan;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

trait InteractsWithPassport
{
    protected function setUpPassport(): void
    {
        if (! file_exists(storage_path('oauth-private.key'))) {
            Artisan::call('passport:keys', ['--force' => true]);
        }

        Passport::loadKeysFrom(storage_path());

        $client = app(ClientRepository::class)->createPasswordGrantClient(
            'Test Password Grant Client',
            'users',
            confidential: true,
        );

        config([
            'passport.password_grant.client_id' => $client->id,
            'passport.password_grant.client_secret' => $client->plainSecret,
        ]);

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Test Platform Personal Access Client',
            'platform_admins',
        );

        app(ClientRepository::class)->createPersonalAccessGrantClient(
            'Test User Personal Access Client',
            'users',
        );
    }
}
