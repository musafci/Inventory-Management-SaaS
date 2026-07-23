<?php

namespace Database\Seeders;

use App\Support\PassportPersonalAccessClients;
use Illuminate\Database\Seeder;

class PlatformPassportSeeder extends Seeder
{
    public function run(): void
    {
        PassportPersonalAccessClients::ensureAll();
    }
}
