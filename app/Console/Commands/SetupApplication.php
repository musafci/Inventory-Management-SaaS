<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class SetupApplication extends Command
{
    protected $signature = 'app:setup
                            {--write-env : Persist generated Passport client credentials to .env}';

    protected $description = 'Bootstrap the app: migrations, seeders, Passport keys, and password-grant client';

    public function handle(): int
    {
        if (blank(config('app.key'))) {
            $this->call('key:generate', ['--force' => true]);
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        if (! file_exists(storage_path('oauth-private.key'))) {
            $this->call('passport:keys', ['--force' => true]);
        }

        $this->call('passport:ensure-password-client', [
            '--write-env' => $this->option('write-env'),
        ]);

        $this->call('passport:ensure-personal-access-clients');

        $this->components->info('Setup complete.');

        return self::SUCCESS;
    }
}
