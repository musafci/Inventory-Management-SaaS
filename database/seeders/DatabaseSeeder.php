<?php

namespace Database\Seeders;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Model::withoutEvents(function (): void {
            $this->call([
                RolesAndPermissionsSeeder::class,
                PlatformPassportSeeder::class,
                PlanSeeder::class,
            ]);
        });

        if (app()->environment('local')) {
            $this->call(DemoSeeder::class);

            Artisan::call('passport:ensure-password-client', [
                '--write-env' => true,
            ]);
        }
    }
}
