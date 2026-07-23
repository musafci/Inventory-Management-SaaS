<?php

namespace App\Console\Commands;

use App\Support\PassportPersonalAccessClients;
use Illuminate\Console\Command;

class EnsurePassportPersonalAccessClients extends Command
{
    protected $signature = 'passport:ensure-personal-access-clients';

    protected $description = 'Ensure Passport personal access clients exist for tenant users and platform admins';

    public function handle(): int
    {
        foreach (['users', 'platform_admins'] as $provider) {
            if (PassportPersonalAccessClients::exists($provider)) {
                $this->components->info("Passport personal access client for [{$provider}] already exists.");

                continue;
            }

            PassportPersonalAccessClients::ensure($provider);
            $this->components->info("Created Passport personal access client for [{$provider}].");
        }

        return self::SUCCESS;
    }
}
