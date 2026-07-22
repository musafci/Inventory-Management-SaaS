<?php

namespace App\Console\Commands;

use App\Services\PlatformAdminService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

class CreatePlatformAdminCommand extends Command
{
    protected $signature = 'platform:admin:create
                            {email : Platform admin email address}
                            {name : Platform admin display name}
                            {--password= : Password (prompted securely when omitted)}';

    protected $description = 'Bootstrap a platform super-admin account';

    public function handle(PlatformAdminService $platformAdminService): int
    {
        $email = (string) $this->argument('email');
        $name = (string) $this->argument('name');
        $password = $this->option('password') ?: $this->secret('Password');

        $validator = Validator::make(
            ['email' => $email, 'name' => $name, 'password' => $password],
            [
                'email' => ['required', 'email'],
                'name' => ['required', 'string', 'max:255'],
                'password' => ['required', 'string', 'min:8'],
            ],
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $admin = $platformAdminService->create($name, $email, (string) $password);

        $this->info("Platform admin created: {$admin->email} (#{$admin->id})");

        return self::SUCCESS;
    }
}
