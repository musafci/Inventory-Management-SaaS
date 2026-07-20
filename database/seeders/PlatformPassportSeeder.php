<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class PlatformPassportSeeder extends Seeder
{
    public function run(): void
    {
        $repository = app(ClientRepository::class);

        if (! Passport::client()->newQuery()
            ->where('revoked', false)
            ->where('provider', 'platform_admins')
            ->exists()) {
            $repository->createPersonalAccessGrantClient(
                'Platform Admin Personal Access Client',
                'platform_admins',
            );
        }
    }
}
