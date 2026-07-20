<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Laravel\Passport\ClientRepository;
use Laravel\Passport\Passport;

class EnsurePassportPasswordClient extends Command
{
    protected $signature = 'passport:ensure-password-client
                            {--write-env : Persist client credentials to .env}';

    protected $description = 'Ensure a valid Passport password-grant client exists and matches .env';

    public function handle(ClientRepository $clients): int
    {
        $clientId = config('passport.password_grant.client_id');
        $clientSecret = config('passport.password_grant.client_secret');

        if ($this->clientCredentialsAreValid($clientId, $clientSecret)) {
            $this->components->info('Passport password-grant client is valid.');

            return self::SUCCESS;
        }

        if ($clientId) {
            $this->components->warn('PASSPORT_PASSWORD_CLIENT_ID in .env does not match a valid client — creating a new one.');
        } else {
            $this->components->warn('Passport password-grant client is not configured — creating one.');
        }

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
            $this->warn('Update .env with the values above, or re-run with --write-env.');
        }

        return self::SUCCESS;
    }

    protected function clientCredentialsAreValid(?string $clientId, ?string $clientSecret): bool
    {
        if (blank($clientId) || blank($clientSecret)) {
            return false;
        }

        $client = Client::query()->find($clientId);

        if ($client === null || $client->revoked) {
            return false;
        }

        if ($client->provider !== null && $client->provider !== 'users') {
            return false;
        }

        if (! $client->hasGrantType('password')) {
            return false;
        }

        return Hash::check($clientSecret, (string) ($client->getAttributes()['secret'] ?? ''));
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
