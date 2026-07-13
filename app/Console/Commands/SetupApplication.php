<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Laravel\Passport\ClientRepository;

class SetupApplication extends Command
{
    protected $signature = 'app:setup
                            {--write-env : Persist generated Passport client credentials to .env}';

    protected $description = 'Bootstrap the app: migrations, seeders, Passport keys, and password-grant client';

    public function handle(ClientRepository $clients): int
    {
        if (blank(config('app.key'))) {
            $this->call('key:generate', ['--force' => true]);
        }

        $this->call('migrate', ['--force' => true]);
        $this->call('db:seed', ['--force' => true]);

        if (! file_exists(storage_path('oauth-private.key'))) {
            $this->call('passport:keys', ['--force' => true]);
        }

        if (blank(config('passport.password_grant.client_id'))) {
            $client = $clients->createPasswordGrantClient(
                'Inventory API Password Grant',
                'users',
                confidential: true,
            );

            $this->components->info('Passport password-grant client created.');
            $this->line("PASSPORT_PASSWORD_CLIENT_ID={$client->id}");
            $this->line("PASSPORT_PASSWORD_CLIENT_SECRET={$client->plainSecret}");

            if ($this->option('write-env')) {
                $this->writeEnvValues([
                    'PASSPORT_PASSWORD_CLIENT_ID' => (string) $client->id,
                    'PASSPORT_PASSWORD_CLIENT_SECRET' => $client->plainSecret,
                ]);
                $this->components->info('Passport credentials written to .env');
            } else {
                $this->warn('Add the Passport values above to your .env, or re-run with --write-env.');
            }
        }

        $this->components->info('Setup complete.');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, string>  $values
     */
    protected function writeEnvValues(array $values): void
    {
        $envPath = base_path('.env');

        if (! file_exists($envPath)) {
            copy(base_path('.env.example'), $envPath);
        }

        $contents = file_get_contents($envPath);

        foreach ($values as $key => $value) {
            $line = "{$key}={$value}";

            if (preg_match("/^{$key}=/m", $contents)) {
                $contents = preg_replace("/^{$key}=.*$/m", $line, $contents);
            } else {
                $contents = rtrim($contents).PHP_EOL.$line.PHP_EOL;
            }
        }

        file_put_contents($envPath, $contents);
    }
}
